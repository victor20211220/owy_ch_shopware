<?php declare(strict_types=1);

namespace Ott\SelectlineImport\ImportTypeProcessor;

use Ott\Base\Import\CollectionService;
use Ott\Base\Import\ImportService;
use Ott\Base\Import\Module\CustomerModule;
use Ott\Base\Import\Module\ProductModule;
use Ott\Base\Service\MediaHelper;
use Ott\SelectlineImport\Dbal\Entity\ImportMessageEntity;
use Ott\SelectlineImport\Gateway\ImportExtensionGateway;
use Ott\SelectlineImport\Service\ImportPictureMessageManager;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\Rule\CustomerCustomFieldRule;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityCollection;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionCollection;
use Shopware\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\System\Tax\TaxEntity;

final class SelectlineImportProcessor extends ImportProcessor implements ImportTypeProcessorInterface
{
    public const PROCESSOR_TYPE = 'product';
    public const DEFAULT_DELIVERYTIME = '4f1c386b388b4c498233211ab04142bd';
    public const DEFAULT_SALESCHANNEL_ID = 'bf965ca112b541b88429fefb2dd63866';
    public const PARENT_CATEGORY = 'Startseite|';
    public const CATEGORY_REMOVE = 'ONLINESHOP|Default Category|';
    public const DISCOUNT_RULE_NAME = 'Kunde hat Rabattgruppe ';
    public const DEFAULT_BADGE_FONT_COLOR = '#FFFFFF';
    public const SALE_CATEGORY_PATH = 'ONLINESHOP|Default Category|Produkte|Ausverkauf';
    private array $ruleCacheEntity = [];

    public function __construct(
        ImportService $importService,
        ProductModule $productModule,
        MediaHelper $mediaService,
        ImportExtensionGateway $importExtensionGateway,
        CustomerModule $customerModule,
        LoggerInterface $logger,
        ImportPictureMessageManager $importPictureMessageManager
    )
    {
        parent::__construct(
            $importService,
            $productModule,
            $mediaService,
            $importExtensionGateway,
            $customerModule,
            $logger,
            $importPictureMessageManager
        );

        $this->importService->disableMediaUploads();
    }

    public function import(ImportMessageEntity $message): void
    {
        /** @var ProductVisibilityCollection $visibilityCollection */
        $data = $message->getWorkload();
        $this->importService->setDefaultLanguage('DE');
        $this->productModule->setIsCustomFieldMergeEnabled(false);

        $mainProductNumber = $data['productNumber'];
        $pseudoPrice = 0;
        $productCustomFields = [];
        $productProperties = [];

        $productId = $this->productModule->selectProductId($mainProductNumber);
        $product = new ProductEntity();
        if (null !== $productId) {
            $product->setId($productId);

            $productCustomFields = $this->importExtensionGateway->selectProductCustomFields(
                $product->getId(),
                $this->importService->getLanguageId()
            );

            $productProperties = $this->importExtensionGateway->selectProductProperties(
                $product->getId(),
                $this->importService->getLanguageId()
            );
        }

        if (empty($data['categories'])) {
            return;
        }

        if (true === (bool) $data['isSale']) {
            $cloneCategories = $data['categories'];
            unset($data['categories']);

            foreach ($cloneCategories as $cloneCategory) {
                $categoryParts = explode('|', $cloneCategory);
                $data['categories'][] = self::SALE_CATEGORY_PATH . '|' . end($categoryParts);
            }
        }

        $categories = $this->createCategories($data['categories']);
        $manufacturer = new ProductManufacturerEntity();
        $manufacturer->setName($data['manufacturer']);

        $tax = new TaxEntity();
        $tax->setTaxRate($data['tax']);
        $tax->setName((string) $data['tax']);

        $productVisibilities = [];
        $productVisibility = new ProductVisibilityEntity();
        $productVisibility->setProduct($product);
        $productVisibility->setSalesChannelId(self::DEFAULT_SALESCHANNEL_ID);
        $productVisibility->setVisibility(ProductVisibilityDefinition::VISIBILITY_ALL);
        $productVisibilities[] = $productVisibility;

        $visibilityCollection = CollectionService::buildCollection(
            $productVisibilities,
            ProductVisibilityCollection::class
        );

        $listprice = (float) $data['listprice'];
        $alternativePrices = [];
        $priceGroups = ['price1', 'price4'];
        $pseudoPrice = 0;
        foreach ($priceGroups as $priceGroup) {
            if (isset($data[$priceGroup]) && 0 < $data[$priceGroup]) {
                $salesKey = 'price1' === $priceGroup ? 'price8' : 'price9';
                $originalPrice = (float) $data[$priceGroup];
                if (true === (bool) $data['isSale']) {
                    $originalPrice = (float) $data[$salesKey];
                }

                $price = $this->importService->getPrice(
                    $originalPrice,
                    $data['tax'],
                    'price1' === $priceGroup,
                    'EUR',
                    false,
                    $pseudoPrice
                );

                $productPrice = new ProductPriceEntity();
                $productPrice->setProduct($product);
                $productPrice->setRuleId($this->importService->getRuleId('Kunde hat Preisgruppe ' . $priceGroup));
                $productPrice->setPrice($price);
                $productPrice->setQuantityStart(1);
                $alternativePrices[] = $productPrice;
            }
        }

        $alternativePrices = $this->calculateDiscountPrices($alternativePrices, $product, $data);

        if (!empty($data['customerPrices']) && isset($data['customerPrices']['Kundenpreis'])) {
            $alternativePrices = $this->calculateCustomerPrices($alternativePrices, $product, $data, $listprice);
        }

        if (!empty($alternativePrices)) {
            $productPriceCollection = CollectionService::buildCollection(
                $alternativePrices,
                ProductPriceCollection::class
            );
            $product->setPrices($productPriceCollection);
        }

        $product->setCategories($categories);
        $product->setManufacturer($manufacturer);
        $product->setEan($data['ean']);
        $product->setWeight((float) $data['weight']);

        $productName = $data['name'];
        while (str_contains($productName, '  ')) {
            $productName = str_replace('  ', ' ', $productName);
        }

        $englishLanguageId = $this->productModule->getLanguageIdByName('EN');
        $frenchLanguageId = $this->productModule->getLanguageIdByName('FR');

        if (!empty($data['textLikeProduct'])) {
            $translationEnglish = $this->importExtensionGateway->getTranslation($data['textLikeProduct'], $englishLanguageId);
            $data['nameEnglish'] = $translationEnglish['name'] ?? '';
            $data['longDescriptionEnglish'] = $translationEnglish['description'] ?? '';

            $translationFrench = $this->importExtensionGateway->getTranslation($data['textLikeProduct'], $frenchLanguageId);
            $data['nameFrench'] = $translationFrench['name'] ?? '';
            $data['longDescriptionFrench'] = $translationFrench['description'] ?? '';
        }

        $product->setName($productName);
        $product->setProductNumber($mainProductNumber);
        $product->setManufacturerNumber($data['manufacturerNumber']);
        $product->setDescription($data['longDescription']);
        $product->setMetaDescription($data['shortDescription']);
        $product->setStock($data['stock']);
        $product->setVisibilities($visibilityCollection);

        if (null !== $data['features']) {
            $properties = array_merge($productProperties, $data['features']);
        }

        if (!empty($properties)) {
            $product->setProperties($this->getFilters($properties));
        }
        $product->setTax($tax);
        $product->setPrice($this->importService->getPrice($listprice, $data['tax'], true, 'EUR', false, $pseudoPrice));

        $productCustomFields['ott_custom_lastupdate'] = $data['importDate'];
        $productCustomFields['custom_additonal_badges_title'] = $data['marker']['text'] ?? '';
        $productCustomFields['custom_additonal_badges_color'] = self::DEFAULT_BADGE_FONT_COLOR;
        $productCustomFields['custom_additonal_badges_bg_color'] = $data['marker']['color'] ?? '';
        $productCustomFields['custom_additonal_badges_bg_color2'] = $data['marker']['color2'] ?? '';

        $product->setCustomFields($productCustomFields);
        $product->setDeliveryTimeId(self::DEFAULT_DELIVERYTIME);

        $productTranslationFrench = new ProductTranslationEntity();
        $productTranslationFrench->setProduct($product);
        $productTranslationFrench->setLanguageId($frenchLanguageId);
        $productTranslationFrench->setName(empty($data['nameFrench']) ? null : $data['nameFrench']);
        $productTranslationFrench->setDescription(empty($data['longDescriptionFrench']) ? null : $data['longDescriptionFrench']);

        $productTranslationEnglish = new ProductTranslationEntity();
        $productTranslationEnglish->setProduct($product);
        $productTranslationEnglish->setLanguageId($englishLanguageId);
        $productTranslationEnglish->setName(empty($data['nameEnglish']) ? null : $data['nameEnglish']);
        $productTranslationEnglish->setDescription(empty($data['longDescriptionEnglish']) ? null : $data['longDescriptionEnglish']);

        $translationCollection = CollectionService::buildCollection(
            [$productTranslationFrench, $productTranslationEnglish],
            ProductTranslationCollection::class
        );

        $product->setTranslations($translationCollection);

        $productId = $this->importService->importProduct($product, true, true, false, true);

        if (0 < \count($data['pictures'])) {
            $this->importPictureMessageManager->generate($productId, $data);
        }
    }

    private function calculateDiscountPrices(
        array $alternativePrices,
        ProductEntity $product,
        array $data
    ): array
    {
        $customField = $this->importExtensionGateway->getCustomFieldIdandSetId();

        $i = 1;
        while (20 >= $i) {
            $discountId = str_pad((string) $i, 2, '0', \STR_PAD_LEFT);
            if (
                isset($data['discount']['discount' . $discountId])
                && 0 < (float) $data['discount']['discount' . $discountId]) {
                if (!isset($ruleCacheEntity[$i])) {
                    $ruleConditionEntity = new RuleConditionEntity();
                    $ruleConditionEntity->setType('customerCustomField');
                    $value = [
                        'operator'      => '=',
                        'renderedField' => [
                            'name'   => ImportExtensionGateway::CUSTOM_FIELD_NAME,
                            'type'   => 'string',
                            'active' => true,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Rabattgruppe',
                                    'en-GB' => 'discount group',
                                ],
                                'componentName'   => 'sw-text-field',
                                'customFieldType' => 'textEditor',
                            ],
                        ],
                        'selectedField'      => $customField['id'],
                        'selectedFieldSet'   => $customField['set_id'],
                        'renderedFieldValue' => 'discount' . $i,
                    ];

                    $ruleConditionEntity->setValue($value);
                    $ruleConditionCollection = CollectionService::buildCollection(
                        [$ruleConditionEntity],
                        RuleConditionCollection::class
                    );

                    $rule = new CustomerCustomFieldRule('=', [
                        'name'   => ImportExtensionGateway::CUSTOM_FIELD_NAME,
                        'type'   => 'string',
                        'active' => true,
                        'config' => [
                            'label' => [
                                'de-DE' => 'Rabattgruppe',
                                'en-GB' => 'discount group',
                            ],
                            'componentName'   => 'sw-text-field',
                            'customFieldType' => 'textEditor',
                        ],
                    ]);

                    $ruleEntity = new RuleEntity();
                    $ruleEntity->setName(self::DISCOUNT_RULE_NAME . $i);
                    $ruleEntity->setPayload($rule);
                    $ruleEntity->setModuleTypes(['types' => ['price']]);
                    $ruleEntity->setPriority(15);
                    $ruleEntity->setConditions($ruleConditionCollection);

                    $ruleCacheEntity[$i] = $ruleEntity;
                }

                $discountPrice = (float) $data['price1'] / 100 * (100 - (float) $data['discount']['discount' . $discountId]);
                $discountPrice = $this->importService->getPrice($discountPrice, $data['tax'], true);
                $productPrice = new ProductPriceEntity();
                $productPrice->setProduct($product);
                $productPrice->setRuleId($this->importService->getRuleId(self::DISCOUNT_RULE_NAME . $i, $ruleCacheEntity[$i]));
                $productPrice->setPrice($discountPrice);
                $productPrice->setQuantityStart(1);
                $alternativePrices[] = $productPrice;
            }
            ++$i;
        }

        return $alternativePrices;
    }

    private function calculateCustomerPrices(
        array $alternativePrices,
        ProductEntity $product,
        array $data,
        float $listprice
    ): array
    {
        $customerPrices = isset($data['customerPrices']['Kundenpreis']['Kundennummer'])
            ? [$data['customerPrices']['Kundenpreis']]
            : $data['customerPrices']['Kundenpreis'];

        foreach ($customerPrices as $customerPrice) {
            $ruleConditionEntity = new RuleConditionEntity();
            $ruleConditionEntity->setType('customerCustomerNumber');
            $ruleConditionEntity->setValue(['numbers' => [$customerPrice['Kundennummer']], 'operator' => '=']);
            $ruleConditionCollection = CollectionService::buildCollection(
                [$ruleConditionEntity],
                RuleConditionCollection::class
            );

            $rule = new CustomerNumberRule('=', [$customerPrice['Kundennummer']]);
            $ruleEntity = new RuleEntity();
            $ruleEntity->setName('Kundenpreis ' . $customerPrice['Kundennummer']);
            $ruleEntity->setPayload($rule);
            $ruleEntity->setModuleTypes(['types' => ['price']]);
            $ruleEntity->setPriority(15);
            $ruleEntity->setConditions($ruleConditionCollection);

            $price = $this->importService->getPrice(
                ($listprice > (float) $customerPrice['Preis']) ? (float) $customerPrice['Preis'] : $listprice,
                $data['tax'],
                true
            );
            $productPrice = new ProductPriceEntity();
            $productPrice->setProduct($product);
            $productPrice->setRuleId($this->importService->getRuleId($ruleEntity->getName(), $ruleEntity));
            $productPrice->setPrice($price);
            $productPrice->setQuantityStart(1);
            $alternativePrices[] = $productPrice;
        }

        return $alternativePrices;
    }

    private function getFilters(array $data): PropertyGroupOptionCollection
    {
        $options = [];
        foreach ($data as $filter) {
            $group = new PropertyGroupEntity();
            $option = new PropertyGroupOptionEntity();
            $group->setName($filter['name']);
            $option->setName($filter['value']);
            $option->setGroup($group);
            $options[] = $option;
        }

        return CollectionService::buildCollection($options, PropertyGroupOptionCollection::class);
    }

    private function createCategories(array $paths): CategoryCollection
    {
        $persistingPaths = [];
        foreach ($paths as $path) {
            $path = self::PARENT_CATEGORY . str_replace(self::CATEGORY_REMOVE, '', $path);
            $persistingPaths[] = ['path' => $path];
        }

        return $this->importService->buildCategoriesByPaths($persistingPaths);
    }
}
