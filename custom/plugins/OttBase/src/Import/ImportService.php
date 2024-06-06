<?php declare(strict_types=1);

namespace Ott\Base\Import;

use Ott\Base\Import\Module\CategoryModule;
use Ott\Base\Import\Module\CustomerModule;
use Ott\Base\Import\Module\ManufacturerModule;
use Ott\Base\Import\Module\MediaModule;
use Ott\Base\Import\Module\NewsletterRecipientModule;
use Ott\Base\Import\Module\OrderModule;
use Ott\Base\Import\Module\PaymentMethodModule;
use Ott\Base\Import\Module\ProductModule;
use Ott\Base\Import\Module\PropertyModule;
use Ott\Base\Import\Module\RuleModule;
use Ott\Base\Import\Module\SalesChannelModule;
use Ott\Base\Import\Module\ShippingMethodModule;
use Ott\Base\Import\Module\StateModule;
use Ott\Base\Import\Module\TagModule;
use Ott\Base\Service\MediaHelper;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroupTranslation\CustomerGroupTranslationDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDeliveryPosition\OrderDeliveryPositionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodTranslation\ShippingMethodTranslationDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityEntity;
use Shopware\Core\Content\Product\DataAbstractionLayer\ProductIndexingMessage;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SearchKeyword\ProductSearchKeywordAnalyzerInterface;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\DeliveryTime\Aggregate\DeliveryTimeTranslation\DeliveryTimeTranslationDefinition;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelTranslation\SalesChannelTranslationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateTranslationDefinition;
use Shopware\Core\System\Tag\TagEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Shopware\Core\System\Unit\UnitEntity;
use Symfony\Component\Messenger\MessageBusInterface;

class ImportService
{
    private const DEFAULT_LANGUAGE = 'English';
    private const DEFAULT_SALES_CHANNEL_TYPE = 'Storefront';
    public const STATE_MACHINE_ORDER = 'order.state';
    public const STATE_MACHINE_ORDER_DELIVERY = 'order_delivery.state';
    public const STATE_MACHINE_ORDER_TRANSACTION = 'order_transaction.state';
    private const DEFAULT_SNIPPET_SET = 'en-GB';
    private ProductModule $productModule;
    private CategoryModule $categoryModule;
    private ManufacturerModule $manufacturerModule;
    private MediaModule $mediaModule;
    private PropertyModule $propertyModule;
    private string $defaultLanguage = self::DEFAULT_LANGUAGE;
    private ?string $defaultLanguageId = null;
    private ProductSearchKeywordAnalyzerInterface $keywordAnalyzer;
    private CustomerModule $customerModule;
    private RuleModule $ruleModule;
    private ShippingMethodModule $shippingMethodModule;
    private MediaHelper $mediaHelper;
    private SalesChannelModule $salesChannelModule;
    private StateModule $stateModule;
    private PaymentMethodModule $paymentMethodModule;
    private OrderModule $orderModule;
    private bool $disabledMediaUploads = false;
    private bool $enableCustomFieldMergeExistingFirst = false;
    private MessageBusInterface $messageBus;
    private TagModule $tagModule;
    private NewsletterRecipientModule $newsletterRecipientModule;

    public function __construct(
        ProductModule $productModule,
        CategoryModule $categoryModule,
        ManufacturerModule $manufacturerModule,
        MediaModule $mediaModule,
        PropertyModule $propertyModule,
        CustomerModule $customerModule,
        RuleModule $ruleModule,
        ShippingMethodModule $shippingMethodModule,
        SalesChannelModule $salesChannelModule,
        StateModule $stateModule,
        PaymentMethodModule $paymentMethodModule,
        OrderModule $orderModule,
        TagModule $tagModule,
        NewsletterRecipientModule $newsletterRecipientModule,
        ProductSearchKeywordAnalyzerInterface $productSearchKeywordAnalyzer,
        MediaHelper $mediaHelper,
        MessageBusInterface $messageBus
    )
    {
        $this->productModule = $productModule;
        $this->categoryModule = $categoryModule;
        $this->manufacturerModule = $manufacturerModule;
        $this->mediaModule = $mediaModule;
        $this->propertyModule = $propertyModule;
        $this->keywordAnalyzer = $productSearchKeywordAnalyzer;
        $this->customerModule = $customerModule;
        $this->ruleModule = $ruleModule;
        $this->shippingMethodModule = $shippingMethodModule;
        $this->mediaHelper = $mediaHelper;
        $this->salesChannelModule = $salesChannelModule;
        $this->stateModule = $stateModule;
        $this->paymentMethodModule = $paymentMethodModule;
        $this->orderModule = $orderModule;
        $this->messageBus = $messageBus;
        $this->tagModule = $tagModule;
        $this->newsletterRecipientModule = $newsletterRecipientModule;
    }

    public function disableCache(): void
    {
        $this->productModule->setIsCacheEnabled(false);
        $this->categoryModule->setIsCacheEnabled(false);
        $this->manufacturerModule->setIsCacheEnabled(false);
        $this->mediaModule->setIsCacheEnabled(false);
        $this->propertyModule->setIsCacheEnabled(false);
        $this->ruleModule->setIsCacheEnabled(false);
        $this->customerModule->setIsCacheEnabled(false);
        $this->shippingMethodModule->setIsCacheEnabled(false);
        $this->salesChannelModule->setIsCacheEnabled(false);
        $this->stateModule->setIsCacheEnabled(false);
        $this->paymentMethodModule->setIsCacheEnabled(false);
        $this->orderModule->setIsCacheEnabled(false);
    }

    public function disableCustomFieldMerge(): void
    {
        $this->productModule->setIsCustomFieldMergeEnabled(false);
        $this->categoryModule->setIsCustomFieldMergeEnabled(false);
        $this->manufacturerModule->setIsCustomFieldMergeEnabled(false);
        $this->mediaModule->setIsCustomFieldMergeEnabled(false);
        $this->propertyModule->setIsCustomFieldMergeEnabled(false);
        $this->ruleModule->setIsCustomFieldMergeEnabled(false);
        $this->customerModule->setIsCustomFieldMergeEnabled(false);
        $this->shippingMethodModule->setIsCustomFieldMergeEnabled(false);
        $this->salesChannelModule->setIsCustomFieldMergeEnabled(false);
        $this->stateModule->setIsCustomFieldMergeEnabled(false);
        $this->paymentMethodModule->setIsCustomFieldMergeEnabled(false);
        $this->orderModule->setIsCustomFieldMergeEnabled(false);
    }

    public function enableCmsPageOverride(): void
    {
        $this->categoryModule->setIsCmsPageOverrideEnabled(true);
    }

    public function disableMediaUploads(): void
    {
        $this->disabledMediaUploads = true;
    }

    public function enableCustomFieldMergeExistingFirst(): void
    {
        $this->enableCustomFieldMergeExistingFirst = true;
    }

    public function setDefaultLanguage(string $defaultLanguage): self
    {
        $this->defaultLanguage = $defaultLanguage;

        return $this;
    }

    public function getDefaultSalesChannel(): string
    {
        return $this->productModule->getDefaultSalesChannel();
    }

    public function getMediaFolderId(string $folderName = 'product'): string
    {
        return $this->productModule->getMediaFolderId($folderName);
    }

    public function updateProductDelta(
        string $productNumber,
        bool $active,
        ?int $stock = null,
        ?float $price = null
    ): void
    {
        $this->productModule->updateProductDeltaInformation($productNumber, $active, $stock, $price);
    }

    public function getMediaId(string $name, string $extension): ?string
    {
        return $this->mediaModule->selectMediaId($name, $extension);
    }

    public function getMediaIdByEntityId(string $table, string $id): ?string
    {
        return $this->productModule->selectMediaIdByEntity($table, $id);
    }

    public function getPrice(
        float $value,
        float $taxRate,
        ?bool $isNet = false,
        ?string $iso = 'EUR',
        ?bool $linked = false,
        ?float $listPrice = null,
        ?array $percentage = null,
        ?float $regulationPrice = null
    ): PriceCollection
    {
        $priceCollection = new PriceCollection();
        $percentageValue = 0;
        if ($isNet) {
            $grossPrice = $value * ((100 + $taxRate) / 100);
            $netPrice = $value;
            if (null !== $listPrice) {
                $grossListPrice = $listPrice * ((100 + $taxRate) / 100);
                $netListPrice = $listPrice;
                $listPrice = new Price(
                    $this->productModule->selectCurrencyId($iso),
                    $netListPrice,
                    $grossListPrice,
                    $linked
                );
                $percentageValue = 0 >= $netPrice ? 0 : (($netListPrice - $netPrice) / $netPrice) * 100;
            }
            if (null !== $regulationPrice) {
                $grossRegulationPrice = $regulationPrice * ((100 + $taxRate) / 100);
                $netRegulationPrice = $regulationPrice;
                $regulationPrice = new Price(
                    $this->productModule->selectCurrencyId($iso),
                    $netRegulationPrice,
                    $grossRegulationPrice,
                    $linked
                );
            }
        } else {
            $grossPrice = $value;
            $netPrice = $value / ((100 + $taxRate) / 100);
            if (null !== $listPrice) {
                $grossListPrice = $listPrice;
                $netListPrice = $listPrice / ((100 + $taxRate) / 100);
                $listPrice = new Price(
                    $this->productModule->selectCurrencyId($iso),
                    $netListPrice,
                    $grossListPrice,
                    $linked
                );
                $percentageValue = 0 >= $netPrice ? 0 : (($netListPrice - $netPrice) / $netPrice) * 100;
            }
            if (null !== $regulationPrice) {
                $grossRegulationPrice = $regulationPrice;
                $netRegulationPrice = $regulationPrice / ((100 + $taxRate) / 100);
                $regulationPrice = new Price(
                    $this->productModule->selectCurrencyId($iso),
                    $netRegulationPrice,
                    $grossRegulationPrice,
                    $linked
                );
            }
        }

        if (0 < $percentageValue && null === $percentage) {
            $percentage = [
                'net'   => $percentageValue,
                'gross' => $percentageValue,
            ];
        }

        $price = new Price(
            $this->productModule->selectCurrencyId($iso),
            $netPrice,
            $grossPrice,
            $linked,
            $listPrice,
            $percentage,
            $regulationPrice
        );
        $priceCollection->add($price);

        return $priceCollection;
    }

    public function buildCategoriesByPaths(
        array $paths,
        string $delimiter = '|',
        bool $disableMediaUploads = false
    ): CategoryCollection
    {
        $categoryCollection = new CategoryCollection();
        foreach ($paths as $path) {
            $categoryId = $this->categoryModule->getCategoryIdByPath(
                \is_array($path) ? $path['path'] : $path,
                $path['languageId'] ?? $this->getLanguageId(),
                $path['parentId'] ?? null,
                $path['cmsPageId'] ?? $this->productModule->getDefaultCmsPage(),
                $path['tags'] ?? null,
                $delimiter,
                $path['active'] ?? true,
                $path['visible'] ?? true,
                $path['childCount'] ?? 0,
                $path['mediaId'] ?? null,
                $path['type'] ?? 'page',
                $path['displayNestedProducts'] ?? true,
                $path['afterCategoryId'] ?? null,
                $path['customFields'] ?? null,
                $path['metaTitle'] ?? null,
                $path['metaDescription'] ?? null,
                $path['metaKeywords'] ?? null,
                $path['cmsText'] ?? null,
                $path['translations'] ?? [],
                $this->disabledMediaUploads || $disableMediaUploads,
                $path['externalLink'] ?? null,
                $path['slotConfig'] ?? null
            );
            if (null !== $categoryId) {
                $category = new CategoryEntity();
                $category->setId($categoryId);
                $categoryCollection->add($category);
            }
        }

        return $categoryCollection;
    }

    public function importVariant(
        ProductEntity $productEntity,
        ?string $parentProductNumber = null,
        bool $cascadePersist = true,
        bool $isDeltaOnly = false,
        bool $mergeExistingCustomFieldsFirst = false
    ): ?string
    {
        if ($isDeltaOnly) {
            if (!$this->hasValue($productEntity)) {
                $productId = $this->productModule->selectProductId($productEntity->getProductNumber());
                if (null === $productId) {
                    return null;
                }
                $productEntity->setId($productId);
            }

            $this->importPrices($productEntity);
            $this->productModule->updateProductDeltaInformation(
                $productEntity->getProductNumber(),
                $productEntity->getActive(),
                $productEntity->getStock(),
                $productEntity->getPrice()
            );

            return $productEntity->getId();
        }

        $this->productModule->startTransaction();
        $this->productModule->disableForeignKeys();

        if (!$this->hasValue($productEntity)) {
            $productId = $this->productModule->selectProductId($productEntity->getProductNumber());
            if (null === $productId) {
                $productId = $this->getRandomHex();
                $productEntity->setId($productId);
            } else {
                $productEntity->setId($productId);
                $this->mergeExistingCustomFields(
                    $productEntity,
                    ProductTranslationDefinition::ENTITY_NAME,
                    'product_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId(),
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $productEntity,
                ProductTranslationDefinition::ENTITY_NAME,
                'product_id',
                $mergeExistingCustomFieldsFirst,
                $this->getLanguageId(),
            );
        }

        if ($cascadePersist && null !== $productEntity->getDeliveryTime()) {
            $deliveryTime = $productEntity->getDeliveryTime();
            if (!$this->hasValue($deliveryTime)) {
                $deliveryTimeId = $this->shippingMethodModule->selectDeliveryTimeId($deliveryTime->getName());
                if (null === $deliveryTimeId) {
                    $deliveryTimeId = $this->getRandomHex();
                    $this->shippingMethodModule->storeDeliveryTime(
                        $deliveryTimeId,
                        $deliveryTime->getMin(),
                        $deliveryTime->getMax(),
                        $deliveryTime->getUnit()
                    );

                    $this->shippingMethodModule->storeDeliveryTimeTranslation(
                        $deliveryTimeId,
                        $this->getLanguageId(),
                        $deliveryTime->getName(),
                        $deliveryTime->getCustomFields()
                    );
                }
            } else {
                $deliveryTimeId = $deliveryTime->getId();
            }

            $productEntity->setDeliveryTimeId($deliveryTimeId);
        }

        if (null === $productEntity->getParentId()) {
            $parentId = $this->productModule->selectProductId($parentProductNumber);
            if (null === $parentId) {
                throw new \Exception(sprintf('Main product %s does not exist', $parentProductNumber));
            }
            $productEntity->setParentId($parentId);
        }

        if (null !== $productEntity->getOptions()) {
            $optionIds = [];
            foreach ($productEntity->getOptions() as $property) {
                if ($cascadePersist) {
                    $this->importPropertyGroup($property->getGroup());
                    $this->importPropertyOption($property);
                }
                $optionIds[] = strtolower($property->getId());
            }
            $productEntity->setOptionIds($optionIds);
        }

        if (null !== $productEntity->getProperties()) {
            $propertyIds = [];
            foreach ($productEntity->getProperties() as $property) {
                $this->importPropertyGroup($property->getGroup());
                $this->importPropertyOption($property);
                $propertyIds[] = strtolower($property->getId());
            }
            $productEntity->setPropertyIds($propertyIds);
        }

        if ($this->disabledMediaUploads) {
            $mediaId = $this->getMediaIdByEntityId(
                ProductDefinition::ENTITY_NAME,
                $productEntity->getId()
            );

            if (null !== $mediaId) {
                $productEntity->setCoverId($mediaId);
            }
        }

        $variantMediaId = null;
        if (null !== $productEntity->getMedia()) {
            foreach ($productEntity->getMedia() as $medium) {
                $medium = $this->importProductMedia($medium);
                $this->productModule->storeProductMedia(
                    $medium->getId(),
                    $productEntity->getId(),
                    $medium->getMediaId(),
                    $this->hasValue($medium, 'position') ? $medium->getPosition() : 1,
                    $medium->getCustomFields()
                );

                if (null === $productEntity->getCover() && null === $productEntity->getCoverId()) {
                    $productEntity->setCoverId($medium->getId());
                }

                if (null === $variantMediaId) {
                    $variantMediaId = $medium->getMediaId();
                }
            }
        }

        if (null !== $productEntity->getCover()) {
            $productEntity->setCoverId($productEntity->getCover()->getId());
        }

        $categoryTree = $this->categoryModule->getProductCategoryTree($productEntity->getParentId());
        if (null !== $categoryTree) {
            $productEntity->setCategoryTree($categoryTree);
            $this->productModule->resetProductCategoryTree($productEntity->getId());
            $this->productModule->storeProductCategoryTree($productEntity->getId(), $categoryTree);
        }

        if ($cascadePersist && null !== $productEntity->getUnit()) {
            $unit = $productEntity->getUnit();
            if (!$this->hasValue($unit)) {
                $unitId = $this->productModule->selectUnitId($unit->getName(), $this->getLanguageId());
                if (null === $unitId) {
                    $unitId = $this->getRandomHex();
                    $this->productModule->storeUnit($unitId);
                    $this->productModule->storeUnitTranslation(
                        $unitId,
                        $this->getLanguageId(),
                        $unit->getShortCode(),
                        $unit->getName(),
                        $unit->getCustomFields()
                    );
                }
            } else {
                $unitId = $unit->getId();
            }

            $productEntity->setUnitId($unitId);
        }

        $this->productModule->storeProduct(
            $productEntity->getId(),
            $productEntity->getProductNumber(),
            $this->hasValue($productEntity, 'active') ? $productEntity->getActive() : true,
            $productEntity->getStock(),
            null,
            $productEntity->getTaxId(),
            $productEntity->getPrice(),
            $productEntity->getParentId(),
            $productEntity->getEan(),
            $productEntity->getOptionIds(),
            null,
            $productEntity->getCategoryTree(),
            $productEntity->getManufacturerNumber(),
            $productEntity->getCoverId(),
            true,
            $productEntity->getIsCloseout(),
            $productEntity->getShippingFree(),
            false,
            $productEntity->getWeight(),
            null,
            null,
            null,
            $productEntity->getDeliveryTimeId(),
            $productEntity->getUnitId(),
            $productEntity->getPurchaseSteps(),
            $productEntity->getMinPurchase(),
            $productEntity->getMaxPurchase(),
            $productEntity->getPurchaseUnit(),
            $productEntity->getReferenceUnit(),
            $productEntity->getAvailableStock(),
            $this->hasValue($productEntity, 'restockTime') ? $productEntity->getRestockTime() : null
        );

        if (empty($productEntity->getCustomSearchKeywords()) && !empty($productEntity->getKeywords())) {
            $productEntity->setCustomSearchKeywords(explode(' ', $productEntity->getKeywords()));
        }

        $this->productModule->storeProductTranslation(
            $productEntity->getId(),
            $this->getLanguageId(),
            $productEntity->getName(),
            $productEntity->getDescription(),
            $productEntity->getCustomFields(),
            $productEntity->getKeywords(),
            $productEntity->getMetaTitle(),
            $productEntity->getMetaDescription(),
            $productEntity->getPackUnit(),
            $productEntity->getCustomSearchKeywords()
        );

        if (null !== $productEntity->getTranslations()) {
            foreach ($productEntity->getTranslations() as $translation) {
                $this->mergeExistingCustomFields(
                    $productEntity,
                    ProductTranslationDefinition::ENTITY_NAME,
                    'product_id',
                    $mergeExistingCustomFieldsFirst,
                    $translation->getLanguageId(),
                    $translation
                );

                if (empty($translation->getCustomSearchKeywords()) && !empty($translation->getKeywords())) {
                    $translation->setCustomSearchKeywords(explode(' ', $translation->getKeywords()));
                }

                $this->productModule->storeProductTranslation(
                    $productEntity->getId(),
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getDescription(),
                    $translation->getCustomFields(),
                    $translation->getKeywords(),
                    $translation->getMetaTitle(),
                    $translation->getMetaDescription(),
                    $translation->getPackUnit(),
                    $translation->getCustomSearchKeywords()
                );
            }
        }

        if (null !== $productEntity->getOptions()) {
            $this->productModule->resetProductOptions($productEntity->getId());
            $this->productModule->storeProductOptions($productEntity->getId(), $productEntity->getOptions());
            foreach ($productEntity->getOptions() as $option) {
                $configuratorSetId = $this->productModule->selectProductConfiguratorSettingId(
                    $productEntity->getParentId(),
                    $option->getId()
                );
                if (null === $configuratorSetId) {
                    $configuratorSetId = $this->getRandomHex();
                }

                $this->productModule->storeProductConfiguration(
                    $productEntity->getParentId(),
                    $option->getId(),
                    $configuratorSetId,
                    $option->getMediaId() ?? $variantMediaId
                );
            }
        }

        if (!empty($productEntity->getProperties())) {
            $this->productModule->resetProductProperties($productEntity->getId());
            $this->productModule->storeProductProperties($productEntity->getId(), $productEntity->getProperties());
        }

        $this->importPrices($productEntity);

        if (null !== $productEntity->getMainCategories()) {
            $persistedMainCategories = $this->productModule->selectProductMainCategories($productEntity->getId());
            foreach ($productEntity->getMainCategories() as $mainCategory) {
                if (!$this->hasValue($mainCategory, 'productId')) {
                    $mainCategory->setProductId($productEntity->getId());
                }
                $this->productModule->storeProductMainCategory(
                    $mainCategory->getProductId(),
                    $mainCategory->getSalesChannelId(),
                    $mainCategory->getCategoryId()
                );

                foreach ($persistedMainCategories as $key => $persistedMainCategory) {
                    if (
                        $persistedMainCategory['sales_channel_id'] === $mainCategory->getSalesChannelId()
                    ) {
                        unset($persistedMainCategories[$key]);
                        break;
                    }
                }
            }

            if (!empty($persistedMainCategories)) {
                foreach ($persistedMainCategories as $persistedMainCategory) {
                    $this->productModule->deleteProductMainCategory($persistedMainCategory['id']);
                }
            }
        }

        if (null !== $productEntity->getTags()) {
            $this->productModule->resetProductTags($productEntity->getId());
            foreach ($productEntity->getTags() as $tagEntity) {
                if (!$this->hasValue($tagEntity)) {
                    $tagEntity->setId($this->productModule->getTagId($tagEntity->getName()));
                }
            }
            $this->productModule->storeProductTags($productEntity->getId(), $productEntity->getTags());
        }

        $this->productModule->updateProductChildCount($productEntity->getParentId());

        try {
            $this->productModule->commitTransaction();
        } catch (\Exception $exception) {
            $this->productModule->rollbackTransaction();
            throw $exception;
        }

        $this->productModule->enableForeignKeys();
        $message = new ProductIndexingMessage([$productEntity->getId()], null);
        $message->setIndexer('product.indexer');
        $this->messageBus->dispatch($message);

        return $productEntity->getId();
    }

    public function importPrices(ProductEntity $productEntity, bool $mergeExistingCustomFieldsFirst = false): void
    {
        $existingProductPrices = $this->productModule->selectProductPrices($productEntity->getId());
        if (null !== $productEntity->getPrices()) {
            foreach ($productEntity->getPrices() as $price) {
                $price = $this->importProductPrice($price);
                foreach ($existingProductPrices as $key => $existingProductPrice) {
                    if ($existingProductPrice['id'] === $price->getId()) {
                        unset($existingProductPrices[$key]);
                    }
                }
                $this->mergeExistingCustomFields(
                    $price,
                    ProductPriceDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
                $this->productModule->storeProductPrice(
                    $price->getId(),
                    $productEntity->getId(),
                    $price->getRuleId(),
                    $price->getPrice(),
                    $price->getQuantityStart(),
                    $price->getQuantityEnd(),
                    $price->getCustomFields()
                );
            }
        }

        if (!empty($existingProductPrices)) {
            foreach ($existingProductPrices as $existingProductPrice) {
                $this->productModule->deleteProductPrice($existingProductPrice['id']);
            }
        }
    }

    public function importOrder(OrderEntity $orderEntity, bool $mergeExistingCustomFieldsFirst = false): string
    {
        $this->orderModule->startTransaction();
        $this->orderModule->disableForeignKeys();

        $shippingAddressId = null;
        if (!$this->hasValue($orderEntity)) {
            $orderId = $this->orderModule->selectOrderId($orderEntity->getOrderNumber());
            if (null === $orderId) {
                $orderId = $this->getRandomHex();
                $orderEntity->setId($orderId);

                $billing = $orderEntity->getBillingAddress();
                $billingId = $this->getRandomHex();
                $billing->setId($billingId);
                $orderEntity->setBillingAddressId($billingId);
                $this->orderModule->storeOrderAddress(
                    $billing->getId(),
                    $orderEntity->getId(),
                    $billing->getCountryId(),
                    $billing->getSalutationId(),
                    $billing->getFirstName(),
                    $billing->getLastName(),
                    $billing->getStreet(),
                    $billing->getCity(),
                    $billing->getZipcode(),
                    $billing->getPhoneNumber(),
                    $billing->getAdditionalAddressLine1(),
                    $billing->getAdditionalAddressLine2(),
                    $billing->getVatId(),
                    $billing->getCompany(),
                    $billing->getDepartment(),
                    $billing->getTitle(),
                    $billing->getCountryStateId(),
                    $billing->getCustomFields()
                );

                $addressCollection = $orderEntity->getAddresses();
                if (!empty($addressCollection)) {
                    foreach ($addressCollection as $address) {
                        $shippingAddressId = $this->getRandomHex();
                        $address->setId($shippingAddressId);
                        $this->orderModule->storeOrderAddress(
                            $address->getId(),
                            $orderEntity->getId(),
                            $address->getCountryId(),
                            $address->getSalutationId(),
                            $address->getFirstName(),
                            $address->getLastName(),
                            $address->getStreet(),
                            $address->getCity(),
                            $address->getZipcode(),
                            $address->getPhoneNumber(),
                            $address->getAdditionalAddressLine1(),
                            $address->getAdditionalAddressLine2(),
                            $address->getVatId(),
                            $address->getCompany(),
                            $address->getDepartment(),
                            $address->getTitle(),
                            $address->getCountryStateId(),
                            $address->getCustomFields()
                        );
                    }
                }
            } else {
                $orderEntity->setId($orderId);
                $orderEntity->setBillingAddressId(
                    $this->orderModule->selectOrderBillingAddressId($orderEntity->getId())
                );
                $shippingAddressId = $this->orderModule->selectLatestAddressId($orderEntity->getId());
                $this->mergeExistingCustomFields(
                    $orderEntity,
                    OrderDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $orderEntity,
                OrderDefinition::ENTITY_NAME,
                'id',
                $mergeExistingCustomFieldsFirst
            );
            $orderEntity->setBillingAddressId($this->orderModule->selectOrderBillingAddressId($orderEntity->getId()));
            $shippingAddressId = $this->orderModule->selectLatestAddressId($orderEntity->getId());
        }

        $customer = $orderEntity->getOrderCustomer();
        if ($this->hasValue($customer, 'customerId') && null === $customer->getCustomerNumber()) {
            $customerData = $this->customerModule->selectCustomer($customer->getCustomerId());
            if (null !== $customerData) {
                $customer->setCustomFields(
                    null !== $customerData['custom_fields']
                        ? json_decode($customerData['custom_fields'], true, 512, \JSON_THROW_ON_ERROR)
                        : null
                );
                $customer->setVatIds(
                    null !== $customerData['vat_ids']
                        ? json_decode($customerData['vat_ids'], true, 512, \JSON_THROW_ON_ERROR)
                        : []
                );
                $customer->setCustomerNumber($customerData['customer_number']);
                $customer->setCompany($customerData['company']);
                $customer->setSalutationId($customerData['salutation_id']);
                $customer->setEmail($customerData['email']);
                $customer->setFirstName($customerData['first_name']);
                $customer->setLastName($customerData['last_name']);
            }
        }

        if (!$this->hasValue($customer)) {
            $customerId = $this->orderModule->selectOrderCustomerId($orderEntity->getId(), $customer->getCustomerId());
            if (null === $customerId) {
                $customerId = $this->getRandomHex();
                $customer->setId($customerId);
            } else {
                $customer->setId($customerId);
                $this->mergeExistingCustomFields(
                    $customer,
                    OrderCustomerDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $customer,
                OrderCustomerDefinition::ENTITY_NAME,
                'id',
                $mergeExistingCustomFieldsFirst
            );
        }

        $this->orderModule->storeOrderCustomer(
            $customer->getId(),
            $orderEntity->getId(),
            $customer->getCustomerId(),
            $customer->getSalutationId(),
            $customer->getFirstName(),
            $customer->getLastName(),
            $customer->getEmail(),
            $customer->getCustomerNumber(),
            $customer->getTitle(),
            $customer->getCompany(),
            $customer->getRemoteAddress(),
            $customer->getCustomFields()
        );

        $this->orderModule->storeOrder(
            $orderEntity->getId(),
            $orderEntity->getStateId(),
            $orderEntity->getOrderNumber(),
            $orderEntity->getCurrencyId(),
            $orderEntity->getLanguageId(),
            $orderEntity->getCurrencyFactor(),
            $orderEntity->getSalesChannelId(),
            $orderEntity->getBillingAddressId(),
            $orderEntity->getPrice(),
            $orderEntity->getOrderDateTime(),
            $orderEntity->getShippingCosts(),
            $orderEntity->getCustomFields(),
            $orderEntity->getDeepLinkCode(),
            $orderEntity->getCampaignCode(),
            $orderEntity->getAffiliateCode(),
            $orderEntity->getItemRounding(),
            $orderEntity->getTotalRounding(),
            $orderEntity->getRuleIds()
        );

        foreach ($orderEntity->getLineItems() as $lineItem) {
            if (!$this->hasValue($lineItem)) {
                $lineItemId = $this->orderModule->selectOrderLineItemId(
                    $orderEntity->getId(),
                    $lineItem->getProductId(),
                    $lineItem->getPosition()
                );
                if (null === $lineItemId) {
                    $lineItemId = $this->getRandomHex();
                    $lineItem->setId($lineItemId);
                } else {
                    $lineItem->setId($lineItemId);
                    $this->mergeExistingCustomFields(
                        $lineItem,
                        OrderLineItemDefinition::ENTITY_NAME,
                        'id',
                        $mergeExistingCustomFieldsFirst
                    );
                }
            } else {
                $this->mergeExistingCustomFields(
                    $lineItem,
                    OrderLineItemDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }

            $this->orderModule->storeOrderLineItem(
                $lineItem->getId(),
                $orderEntity->getId(),
                $lineItem->getProductId(),
                $lineItem->getType(),
                $lineItem->getLabel(),
                $lineItem->getQuantity(),
                $lineItem->getPayload(),
                $lineItem->getPrice(),
                $lineItem->getPriceDefinition(),
                $lineItem->getDescription(),
                $lineItem->getCoverId(),
                $lineItem->getParentId(),
                $lineItem->getCustomFields()
            );
        }

        foreach ($orderEntity->getDeliveries() as $delivery) {
            if (!$this->hasValue($delivery)) {
                $deliveryId = $this->orderModule->selectOrderDeliveryId(
                    $orderEntity->getId()
                );
                if (null === $deliveryId) {
                    $deliveryId = $this->getRandomHex();
                    $delivery->setId($deliveryId);
                } else {
                    $delivery->setId($deliveryId);
                    $this->mergeExistingCustomFields(
                        $delivery,
                        OrderDeliveryDefinition::ENTITY_NAME,
                        'id',
                        $mergeExistingCustomFieldsFirst
                    );
                }
            } else {
                $this->mergeExistingCustomFields(
                    $delivery,
                    OrderDeliveryDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }

            $this->orderModule->storeOrderDelivery(
                $delivery->getId(),
                $orderEntity->getId(),
                $delivery->getStateId(),
                $shippingAddressId ?? $orderEntity->getBillingAddressId(),
                $delivery->getShippingMethodId(),
                $delivery->getShippingCosts(),
                $delivery->getShippingDateEarliest(),
                $delivery->getShippingDateLatest(),
                $delivery->getTrackingCodes(),
                $delivery->getCustomFields()
            );

            if (!empty($delivery->getPositions())) {
                foreach ($delivery->getPositions() as $deliveryPosition) {
                    if (!$this->hasValue($deliveryPosition)) {
                        $deliveryPositionId = $this->orderModule->selectOrderDeliveryPositionId(
                            $delivery->getId(),
                            $deliveryPosition->getOrderLineItemId()
                        );
                        if (null === $deliveryPositionId) {
                            $deliveryPositionId = $this->getRandomHex();
                            $deliveryPosition->setId($deliveryPositionId);
                        } else {
                            $deliveryPosition->setId($deliveryPositionId);
                            $this->mergeExistingCustomFields(
                                $deliveryPosition,
                                OrderDeliveryPositionDefinition::ENTITY_NAME,
                                'id',
                                $mergeExistingCustomFieldsFirst
                            );
                        }
                    } else {
                        $this->mergeExistingCustomFields(
                            $deliveryPosition,
                            OrderDeliveryPositionDefinition::ENTITY_NAME,
                            'id',
                            $mergeExistingCustomFieldsFirst
                        );
                    }

                    $this->orderModule->storeOrderDeliveryPosition(
                        $deliveryPosition->getId(),
                        $delivery->getId(),
                        $deliveryPosition->getOrderLineItem()->getId(),
                        $deliveryPosition->getPrice(),
                        $deliveryPosition->getCustomFields()
                    );
                }
            }
        }

        foreach ($orderEntity->getTransactions() as $transaction) {
            if (!$this->hasValue($transaction)) {
                $transactionId = $this->orderModule->selectOrderTransactionId(
                    $orderEntity->getId(),
                    $transaction->getPaymentMethodId()
                );
                if (null === $transactionId) {
                    $transactionId = $this->getRandomHex();
                    $transaction->setId($transactionId);
                } else {
                    $transaction->setId($transactionId);
                    $this->mergeExistingCustomFields(
                        $transaction,
                        OrderTransactionDefinition::ENTITY_NAME,
                        'id',
                        $mergeExistingCustomFieldsFirst
                    );
                }
            } else {
                $this->mergeExistingCustomFields(
                    $transaction,
                    OrderTransactionDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }

            $this->orderModule->storeOrderTransaction(
                $transaction->getId(),
                $orderEntity->getId(),
                $transaction->getStateId(),
                $transaction->getPaymentMethodId(),
                $transaction->getAmount(),
                $transaction->getCustomFields()
            );
        }

        try {
            $this->orderModule->commitTransaction();
        } catch (\Exception $exception) {
            $this->orderModule->rollbackTransaction();
            throw $exception;
        }

        $this->orderModule->enableForeignKeys();

        return $orderEntity->getId();
    }

    public function importProduct(
        ProductEntity $productEntity,
        bool $createKeywords = true,
        bool $cascadePersist = true,
        bool $isDeltaOnly = false,
        bool $noUpdate = false,
        bool $mergeExistingCustomFieldsFirst = false
    ): ?string
    {
        if ($isDeltaOnly) {
            if (!$this->hasValue($productEntity)) {
                $productId = $this->productModule->selectProductId($productEntity->getProductNumber());
                if (null === $productId) {
                    return null;
                }
                $productEntity->setId($productId);
            }

            $this->importPrices($productEntity);
            $this->productModule->updateProductDeltaInformation(
                $productEntity->getProductNumber(),
                $productEntity->getActive(),
                $productEntity->getStock(),
                $productEntity->getPrice()
            );

            return $productEntity->getId();
        }

        $this->productModule->startTransaction();
        $this->productModule->disableForeignKeys();

        if (!$this->hasValue($productEntity)) {
            $productId = $this->productModule->selectProductId($productEntity->getProductNumber());
            if (null === $productId) {
                $productId = $this->getRandomHex();
                $productEntity->setId($productId);
            } else {
                $productEntity->setId($productId);
                $this->mergeExistingCustomFields(
                    $productEntity,
                    ProductTranslationDefinition::ENTITY_NAME,
                    'product_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $productEntity,
                ProductTranslationDefinition::ENTITY_NAME,
                'product_id',
                $mergeExistingCustomFieldsFirst,
                $this->getLanguageId()
            );
        }

        if ($cascadePersist) {
            if (null !== $productEntity->getManufacturer()) {
                $productManufacturerEntity = $this->importManufacturer($productEntity->getManufacturer(), $noUpdate);
                $productEntity->setManufacturerId($productManufacturerEntity->getId());
            }

            if (null !== $productEntity->getTax()) {
                $taxEntity = $this->importTax($productEntity->getTax());
                $productEntity->setTaxId($taxEntity->getId());
            }

            if (null !== $productEntity->getCategories()) {
                foreach ($productEntity->getCategories() as $category) {
                    if (!$this->hasValue($category, 'name')) {
                        continue;
                    }

                    $this->importCategory($category);
                }
            }

            if (null !== $productEntity->getProperties()) {
                $propertyIds = [];
                foreach ($productEntity->getProperties() as $property) {
                    $this->importPropertyGroup($property->getGroup());
                    $this->importPropertyOption($property);
                    $propertyIds[] = strtolower($property->getId());
                }
                $productEntity->setPropertyIds($propertyIds);
            }

            if (null !== $productEntity->getDeliveryTime()) {
                $deliveryTime = $productEntity->getDeliveryTime();
                if (!$this->hasValue($deliveryTime)) {
                    $deliveryTimeId = $this->shippingMethodModule->selectDeliveryTimeId($deliveryTime->getName());
                    if (null === $deliveryTimeId) {
                        $deliveryTimeId = $this->getRandomHex();
                        $this->shippingMethodModule->storeDeliveryTime(
                            $deliveryTimeId,
                            $deliveryTime->getMin(),
                            $deliveryTime->getMax(),
                            $deliveryTime->getUnit()
                        );

                        $this->shippingMethodModule->storeDeliveryTimeTranslation(
                            $deliveryTimeId,
                            $this->getLanguageId(),
                            $deliveryTime->getName(),
                            $deliveryTime->getCustomFields()
                        );
                    }
                } else {
                    $deliveryTimeId = $deliveryTime->getId();
                }

                $productEntity->setDeliveryTimeId($deliveryTimeId);
            }

            if (null !== $productEntity->getUnit()) {
                $unit = $productEntity->getUnit();
                if (!$this->hasValue($unit)) {
                    $unitId = $this->productModule->selectUnitId($unit->getName(), $this->getLanguageId());
                    if (null === $unitId) {
                        $unitId = $this->getRandomHex();
                        $this->productModule->storeUnit($unitId);
                        $this->productModule->storeUnitTranslation(
                            $unitId,
                            $this->getLanguageId(),
                            $unit->getShortCode(),
                            $unit->getName(),
                            $unit->getCustomFields()
                        );
                    }
                } else {
                    $unitId = $unit->getId();
                }

                $productEntity->setUnitId($unitId);
            }
        }

        if ($this->disabledMediaUploads) {
            $mediaId = $this->getMediaIdByEntityId(
                ProductDefinition::ENTITY_NAME,
                $productEntity->getId()
            );

            if (null !== $mediaId) {
                $productEntity->setCoverId($mediaId);
            }
        }

        if (null !== $productEntity->getMedia()) {
            foreach ($productEntity->getMedia() as $medium) {
                $medium = $this->importProductMedia($medium);
                $this->productModule->storeProductMedia(
                    $medium->getId(),
                    $productEntity->getId(),
                    $medium->getMediaId(),
                    $this->hasValue($medium, 'position') ? $medium->getPosition() : 1,
                    $medium->getCustomFields()
                );

                if (null === $productEntity->getCoverId()) {
                    $productEntity->setCoverId($medium->getId());
                }
            }
        }

        if (!$this->hasValue($productEntity, 'displayGroup')) {
            $displayId = $productEntity->getParentId() ?? $productEntity->getId();
            $productEntity->setDisplayGroup(md5($displayId));
        }

        $this->productModule->storeProduct(
            $productEntity->getId(),
            $productEntity->getProductNumber(),
            $this->hasValue($productEntity, 'active') ? $productEntity->getActive() : true,
            $productEntity->getStock(),
            $productEntity->getManufacturerId(),
            $productEntity->getTaxId(),
            $productEntity->getPrice(),
            $productEntity->getParentId(),
            $productEntity->getEan(),
            $productEntity->getOptionIds(),
            $productEntity->getPropertyIds(),
            $productEntity->getCategoryTree(),
            $productEntity->getManufacturerNumber(),
            $productEntity->getCoverId(),
            $this->hasValue($productEntity, 'available') ? $productEntity->getAvailable() : true,
            $productEntity->getIsCloseout(),
            $productEntity->getShippingFree() ?? false,
            $productEntity->getMarkAsTopseller(),
            $productEntity->getWeight(),
            $productEntity->getWidth(),
            $productEntity->getHeight(),
            $productEntity->getLength(),
            $productEntity->getDeliveryTimeId(),
            $productEntity->getUnitId(),
            $productEntity->getPurchaseSteps() ?? 1,
            $productEntity->getMinPurchase() ?? 1,
            $productEntity->getMaxPurchase(),
            $productEntity->getPurchaseUnit(),
            $productEntity->getReferenceUnit(),
            $productEntity->getAvailableStock(),
            $this->hasValue($productEntity, 'restockTime') ? $productEntity->getRestockTime() : 3,
            $productEntity->getReleaseDate(),
            $productEntity->getRatingAverage(),
            $productEntity->getDisplayGroup(),
            $productEntity->getChildCount(),
            $productEntity->getTagIds(),
            $productEntity->getVariantRestrictions()
        );

        if (empty($productEntity->getCustomSearchKeywords()) && !empty($productEntity->getKeywords())) {
            $productEntity->setCustomSearchKeywords(explode(' ', $productEntity->getKeywords()));
        }

        $this->productModule->storeProductTranslation(
            $productEntity->getId(),
            $this->getLanguageId(),
            $productEntity->getName(),
            $productEntity->getDescription(),
            $productEntity->getCustomFields(),
            $productEntity->getKeywords(),
            $productEntity->getMetaTitle(),
            $productEntity->getMetaDescription(),
            $productEntity->getPackUnit(),
            $productEntity->getCustomSearchKeywords()
        );

        if (null !== $productEntity->getTranslations()) {
            foreach ($productEntity->getTranslations() as $translation) {
                $this->mergeExistingCustomFields(
                    $productEntity,
                    ProductTranslationDefinition::ENTITY_NAME,
                    'product_id',
                    $mergeExistingCustomFieldsFirst,
                    $translation->getLanguageId(),
                    $translation
                );

                if (empty($translation->getCustomSearchKeywords()) && !empty($translation->getKeywords())) {
                    $translation->setCustomSearchKeywords(explode(' ', $translation->getKeywords()));
                }

                $this->productModule->storeProductTranslation(
                    $productEntity->getId(),
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getDescription(),
                    $translation->getCustomFields(),
                    $translation->getKeywords(),
                    $translation->getMetaTitle(),
                    $translation->getMetaDescription(),
                    $translation->getPackUnit(),
                    $translation->getCustomSearchKeywords()
                );
            }
        }

        if (null !== $productEntity->getVisibilities()) {
            $this->productModule->resetProductVisibility($productEntity->getId());
            foreach ($productEntity->getVisibilities() as $visibility) {
                $visibility = $this->importProductVisibility($visibility);
                $this->productModule->storeProductVisibility(
                    $visibility->getId(),
                    $productEntity->getId(),
                    $visibility->getSalesChannelId(),
                    $visibility->getVisibility()
                );
            }
        }

        $this->importPrices($productEntity);

        if (null !== $productEntity->getProductReviews()) {
            foreach ($productEntity->getProductReviews() as $productReview) {
                $productReview->setProductId($productEntity->getId());
                $productReview = $this->importProductReview($productReview);

                if (null === $productReview->getCreatedAt()) {
                    $productReview->setCreatedAt(new \DateTime());
                }

                $this->mergeExistingCustomFields(
                    $productReview,
                    ProductReviewDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );

                $this->productModule->storeProductReview(
                    $productReview->getId(),
                    $productEntity->getId(),
                    $productReview->getCustomerId(),
                    $this->getLanguageId(),
                    $productReview->getSalesChannelId(),
                    $productReview->getStatus(),
                    $productReview->getTitle(),
                    $productReview->getCreatedAt(),
                    $productReview->getContent(),
                    $productReview->getPoints(),
                    $productReview->getComment(),
                    $productReview->getExternalUser(),
                    $productReview->getExternalEmail(),
                    $productReview->getCustomFields(),
                );
            }
        }

        if (!empty($productEntity->getCategories())) {
            if (null === $productEntity->getCategoryTree()) {
                $categoryTree = [];
                foreach ($productEntity->getCategories() as $category) {
                    $categoryTree = array_merge(
                        $categoryTree,
                        $this->categoryModule->getCategoryTree($category->getId())
                    );
                }
                $this->productModule->updateProductCategoryTree($productEntity->getId(), $categoryTree);
                $productEntity->setCategoryTree($categoryTree);
            }
            $this->productModule->resetProductCategories($productEntity->getId());
            $this->productModule->resetProductCategoryTree($productEntity->getId());
            $this->productModule->storeProductCategories($productEntity->getId(), $productEntity->getCategories());
            $this->productModule->storeProductCategoryTree($productEntity->getId(), $productEntity->getCategoryTree());
        }

        if (null !== $productEntity->getMainCategories()) {
            $persistedMainCategories = $this->productModule->selectProductMainCategories($productEntity->getId());
            foreach ($productEntity->getMainCategories() as $mainCategory) {
                if (!$this->hasValue($mainCategory, 'productId')) {
                    $mainCategory->setProductId($productEntity->getId());
                }
                $this->productModule->storeProductMainCategory(
                    $mainCategory->getProductId(),
                    $mainCategory->getSalesChannelId(),
                    $mainCategory->getCategoryId()
                );

                foreach ($persistedMainCategories as $key => $persistedMainCategory) {
                    if (
                        $persistedMainCategory['sales_channel_id'] === $mainCategory->getSalesChannelId()
                    ) {
                        unset($persistedMainCategories[$key]);
                        break;
                    }
                }
            }

            if (!empty($persistedMainCategories)) {
                foreach ($persistedMainCategories as $persistedMainCategory) {
                    $this->productModule->deleteProductMainCategory($persistedMainCategory['id']);
                }
            }
        }

        if (null !== $productEntity->getTags()) {
            $this->productModule->resetProductTags($productEntity->getId());
            foreach ($productEntity->getTags() as $tagEntity) {
                if (!$this->hasValue($tagEntity)) {
                    $tagEntity->setId($this->productModule->getTagId($tagEntity->getName()));
                }
            }
            $this->productModule->storeProductTags($productEntity->getId(), $productEntity->getTags());
        }

        if (!empty($productEntity->getProperties())) {
            $this->productModule->resetProductProperties($productEntity->getId());
            $this->productModule->storeProductProperties($productEntity->getId(), $productEntity->getProperties());
        }

        if (null !== $productEntity->getCrossSellings()) {
            $persistedProductCrossSellings = $this->productModule->getProductCrossSellings($productEntity->getId());
            $crossSellingsToKeep = [];
            foreach ($productEntity->getCrossSellings() as $crossSelling) {
                if (
                    !$this->hasValue($crossSelling, 'productId')
                    && !$this->hasValue($crossSelling, 'productStreamId')
                ) {
                    $crossSelling->setProductId($productEntity->getId());
                }
                $crossSellingsToKeep[] = $this->importProductCrossSelling($crossSelling);
            }

            foreach ($persistedProductCrossSellings as $persistedProductCrossSelling) {
                if (!\in_array($persistedProductCrossSelling['id'], $crossSellingsToKeep)) {
                    $this->productModule->deleteProductCrossSelling($persistedProductCrossSelling['id']);
                }
            }
        }

        if ($createKeywords) {
            $configFields = $this->productModule->getKeywordConfigFields($this->getLanguageId());
            $keywords = $this->keywordAnalyzer->analyze($productEntity, Context::createDefaultContext(), $configFields);
            $this->productModule->resetProductKeywords($productEntity->getId());
            foreach ($keywords as $keyword) {
                $this->productModule->storeProductSearchKeyword(
                    $productEntity->getId(),
                    $this->getLanguageId(),
                    $keyword
                );
            }
        }

        if ($this->hasValue($productEntity, 'variantListingConfig')) {
            $this->productModule->updateProductVariantDisplayConfiguration(
                $productEntity->getId(),
                $productEntity->getVariantListingConfig(),
            );
        }

        try {
            $this->productModule->commitTransaction();
        } catch (\Exception $exception) {
            $this->productModule->rollbackTransaction();
            throw $exception;
        }

        $this->productModule->enableForeignKeys();
        $message = new ProductIndexingMessage([$productEntity->getId()], null);
        $message->setIndexer('product.indexer');
        $this->messageBus->dispatch($message);

        return $productEntity->getId();
    }

    public function importTax(TaxEntity $taxEntity): TaxEntity
    {
        if (!$this->hasValue($taxEntity)) {
            $taxId = $this->productModule->selectTaxId($taxEntity->getTaxRate());
            if (null === $taxId) {
                $taxId = $this->getRandomHex();
                $taxEntity->setId($taxId);

                if (empty($taxEntity->getName())) {
                    $taxEntity->setName($taxEntity->getTaxRate() . '%s');
                }

                $this->productModule->storeTax(
                    $taxEntity->getId(),
                    $taxEntity->getTaxRate(),
                    $taxEntity->getName(),
                    $taxEntity->getCustomFields()
                );
            } else {
                $taxEntity->setId($taxId);
            }
        }

        return $taxEntity;
    }

    public function importManufacturer(
        ProductManufacturerEntity $productManufacturerEntity,
        bool $noUpdate = false,
        bool $mergeExistingCustomFieldsFirst = false
    ): ProductManufacturerEntity
    {
        if (!$this->hasValue($productManufacturerEntity)) {
            $manufacturerId = $this->manufacturerModule->selectManufacturerId(
                $productManufacturerEntity->getName(),
                $this->getLanguageId()
            );
            if (null === $manufacturerId) {
                $productManufacturerEntity->setId($this->getRandomHex());
            } else {
                $productManufacturerEntity->setId($manufacturerId);
                $this->mergeExistingCustomFields(
                    $productManufacturerEntity,
                    ProductManufacturerTranslationDefinition::ENTITY_NAME,
                    'product_manufacturer_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $productManufacturerEntity,
                ProductManufacturerTranslationDefinition::ENTITY_NAME,
                'product_manufacturer_id',
                $mergeExistingCustomFieldsFirst,
                $this->getLanguageId()
            );
        }

        if ($this->disabledMediaUploads) {
            $mediaId = $this->getMediaIdByEntityId(
                ProductManufacturerDefinition::ENTITY_NAME,
                $productManufacturerEntity->getId()
            );

            if (null !== $mediaId) {
                $productManufacturerEntity->setMediaId($mediaId);
            }
        }

        $this->manufacturerModule->storeManufacturer(
            $productManufacturerEntity->getId(),
            $productManufacturerEntity->getLink(),
            $productManufacturerEntity->getMediaId(),
            $noUpdate
        );
        $this->manufacturerModule->storeManufacturerTranslation(
            $productManufacturerEntity->getId(),
            $this->getLanguageId(),
            $productManufacturerEntity->getName(),
            $productManufacturerEntity->getDescription(),
            $productManufacturerEntity->getCustomFields(),
            $noUpdate
        );

        if (null !== $productManufacturerEntity->getTranslations()) {
            foreach ($productManufacturerEntity->getTranslations() as $translation) {
                $this->mergeExistingCustomFields(
                    $productManufacturerEntity,
                    ProductManufacturerTranslationDefinition::ENTITY_NAME,
                    'product_manufacturer_id',
                    $mergeExistingCustomFieldsFirst,
                    $translation->getLanguageId(),
                    $translation
                );
                $this->manufacturerModule->storeManufacturerTranslation(
                    $productManufacturerEntity->getId(),
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getDescription(),
                    $translation->getCustomFields()
                );
            }
        }

        return $productManufacturerEntity;
    }

    public function importCategory(
        CategoryEntity $categoryEntity,
        bool $mergeExistingCustomFieldsFirst = false
    ): CategoryEntity
    {
        if (!$this->hasValue($categoryEntity)) {
            $categoryId = $this->categoryModule->selectCategoryId(
                $categoryEntity->getName(),
                $this->getLanguageId(),
                $categoryEntity->getParentId()
            );
            if (null === $categoryId) {
                $categoryEntity->setId($this->getRandomHex());
            } else {
                $categoryEntity->setId($categoryId);
                $this->mergeExistingCustomFields(
                    $categoryEntity,
                    CategoryTranslationDefinition::ENTITY_NAME,
                    'category_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $categoryEntity,
                CategoryTranslationDefinition::ENTITY_NAME,
                'category_id',
                $mergeExistingCustomFieldsFirst,
                $this->getLanguageId()
            );
        }

        if ($this->disabledMediaUploads) {
            $mediaId = $this->getMediaIdByEntityId(
                CategoryDefinition::ENTITY_NAME,
                $categoryEntity->getId()
            );

            if (null !== $mediaId) {
                $categoryEntity->setMediaId($mediaId);
            }
        }

        $this->categoryModule->storeCategory(
            $categoryEntity->getId(),
            $categoryEntity->getLevel(),
            $categoryEntity->getPath(),
            $categoryEntity->getParentId(),
            $categoryEntity->getCmsPageId(),
            $categoryEntity->getActive(),
            $categoryEntity->getVisible(),
            $categoryEntity->getChildCount(),
            $categoryEntity->getMediaId(),
            $categoryEntity->getType(),
            $categoryEntity->getDisplayNestedProducts()
        );

        $this->categoryModule->storeCategoryTranslation(
            $categoryEntity->getId(),
            $this->getLanguageId(),
            $categoryEntity->getName(),
            $categoryEntity->getDescription(),
            $categoryEntity->getBreadcrumb(),
            $categoryEntity->getMetaTitle(),
            $categoryEntity->getMetaDescription(),
            $categoryEntity->getKeywords(),
            $categoryEntity->getCustomFields(),
            $categoryEntity->getExternalLink(),
            $categoryEntity->getSlotConfig()
        );

        if (null !== $categoryEntity->getTranslations()) {
            foreach ($categoryEntity->getTranslations() as $translation) {
                $this->mergeExistingCustomFields(
                    $categoryEntity,
                    CategoryTranslationDefinition::ENTITY_NAME,
                    'category_id',
                    $mergeExistingCustomFieldsFirst,
                    $translation->getLanguageId(),
                    $translation
                );
                $this->categoryModule->storeCategoryTranslation(
                    $categoryEntity->getId(),
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getDescription(),
                    $translation->getBreadcrumb(),
                    $translation->getMetaTitle(),
                    $translation->getMetaDescription(),
                    $translation->getKeywords(),
                    $translation->getCustomFields(),
                    $translation->getExternalLink(),
                    $translation->getSlotConfig()
                );
            }
        }

        $this->categoryModule->resetCategoryTags($categoryEntity->getId());
        $this->categoryModule->storeCategoryTags($categoryEntity->getId(), $categoryEntity->getTags());

        return $categoryEntity;
    }

    public function importProductMedia(
        ProductMediaEntity $productMediaEntity,
        bool $mergeExistingCustomFieldsFirst = false,
    ): ProductMediaEntity
    {
        if (!$this->hasValue($productMediaEntity)) {
            $productMediaId = $this->productModule->selectProductMediaId(
                $productMediaEntity->getProduct()->getId(),
                $productMediaEntity->getMediaId()
            );

            if (null === $productMediaId) {
                $productMediaId = $this->getRandomHex();
                $productMediaEntity->setId($productMediaId);
            } else {
                $productMediaEntity->setId($productMediaId);
                $this->mergeExistingCustomFields(
                    $productMediaEntity,
                    ProductMediaDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $productMediaEntity,
                ProductMediaDefinition::ENTITY_NAME,
                'id',
                $mergeExistingCustomFieldsFirst
            );
        }

        return $productMediaEntity;
    }

    public function importProductVisibility(ProductVisibilityEntity $productVisibilityEntity): ProductVisibilityEntity
    {
        if (!$this->hasValue($productVisibilityEntity)) {
            $productVisibilityId = $this->productModule->selectProductVisibilityId(
                $productVisibilityEntity->getProduct()->getId(),
                $productVisibilityEntity->getSalesChannelId()
            );

            if (null === $productVisibilityId) {
                $productVisibilityId = $this->getRandomHex();
                $productVisibilityEntity->setId($productVisibilityId);
            } else {
                $productVisibilityEntity->setId($productVisibilityId);
            }
        }

        return $productVisibilityEntity;
    }

    public function importProductPrice(ProductPriceEntity $productPriceEntity): ProductPriceEntity
    {
        if (!$this->hasValue($productPriceEntity)) {
            $productPriceId = $this->productModule->selectProductPriceId(
                $productPriceEntity->getProduct()->getId(),
                $productPriceEntity->getRuleId(),
                $productPriceEntity->getQuantityStart()
            );

            if (null === $productPriceId) {
                $productPriceId = $this->getRandomHex();
                $productPriceEntity->setId($productPriceId);
            } else {
                $productPriceEntity->setId($productPriceId);
            }
        }

        return $productPriceEntity;
    }

    public function importProductReview(ProductReviewEntity $productReviewEntity): ProductReviewEntity
    {
        if (!$this->hasValue($productReviewEntity)) {
            $productReviewId = $this->productModule->selectProductReviewId(
                $productReviewEntity->getProductId(),
                $productReviewEntity->getExternalUser(),
                $productReviewEntity->getExternalEmail()
            );

            if (null === $productReviewId) {
                $productReviewId = $this->getRandomHex();
            }

            $productReviewEntity->setId($productReviewId);
        }

        return $productReviewEntity;
    }

    public function importPropertyGroup(PropertyGroupEntity $propertyGroupEntity): PropertyGroupEntity
    {
        if (!$this->hasValue($propertyGroupEntity)) {
            $propertyGroupId = $this->propertyModule->selectPropertyGroupId(
                $propertyGroupEntity->getName(),
                $this->getLanguageId()
            );
            if (null === $propertyGroupId) {
                $propertyGroupId = $this->getRandomHex();
                $propertyGroupEntity->setId($propertyGroupId);
                $this->propertyModule->storePropertyGroup(
                    $propertyGroupEntity->getId(),
                    $this->hasValue($propertyGroupEntity, 'sortingType')
                        ? $propertyGroupEntity->getSortingType()
                        : 'alphanumeric',
                    $this->hasValue($propertyGroupEntity, 'displayType')
                        ? $propertyGroupEntity->getDisplayType()
                        : 'text'
                );

                $this->propertyModule->storePropertyGroupTranslation(
                    $propertyGroupEntity->getId(),
                    $this->getLanguageId(),
                    $propertyGroupEntity->getName(),
                    $propertyGroupEntity->getDescription(),
                    $propertyGroupEntity->getCustomFields()
                );

                if (null !== $propertyGroupEntity->getTranslations()) {
                    foreach ($propertyGroupEntity->getTranslations() as $translation) {
                        $this->propertyModule->storePropertyGroupTranslation(
                            $propertyGroupEntity->getId(),
                            $translation->getLanguageId(),
                            $translation->getName(),
                            $translation->getDescription(),
                            $translation->getCustomFields()
                        );
                    }
                }
            } else {
                $propertyGroupEntity->setId($propertyGroupId);
            }
        }

        return $propertyGroupEntity;
    }

    public function importPropertyOption(
        PropertyGroupOptionEntity $propertyGroupOptionEntity
    ): PropertyGroupOptionEntity
    {
        if (!$this->hasValue($propertyGroupOptionEntity)) {
            $propertyOptionId = $this->propertyModule->selectPropertyOptionId(
                $propertyGroupOptionEntity->getGroup()->getId(),
                $propertyGroupOptionEntity->getName(),
                $this->getLanguageId()
            );

            if (null === $propertyOptionId) {
                $propertyOptionId = $this->getRandomHex();
            }
            $propertyGroupOptionEntity->setId($propertyOptionId);
        }

        if ($this->disabledMediaUploads) {
            $mediaId = $this->getMediaIdByEntityId(
                PropertyGroupOptionDefinition::ENTITY_NAME,
                $propertyGroupOptionEntity->getId()
            );

            if (null !== $mediaId) {
                $propertyGroupOptionEntity->setMediaId($mediaId);
            }
        }

        $this->propertyModule->storePropertyOption(
            $propertyGroupOptionEntity->getId(),
            $propertyGroupOptionEntity->getGroup()->getId(),
            $propertyGroupOptionEntity->getMediaId(),
            $propertyGroupOptionEntity->getColorHexCode()
        );

        $this->propertyModule->storePropertyOptionTranslation(
            $propertyGroupOptionEntity->getId(),
            $propertyGroupOptionEntity->getGroup()->getId(),
            $this->getLanguageId(),
            $propertyGroupOptionEntity->getName(),
            $propertyGroupOptionEntity->getCustomFields()
        );

        if (null !== $propertyGroupOptionEntity->getTranslations()) {
            foreach ($propertyGroupOptionEntity->getTranslations() as $translation) {
                $this->propertyModule->storePropertyOptionTranslation(
                    $propertyGroupOptionEntity->getId(),
                    $propertyGroupOptionEntity->getGroup()->getId(),
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getCustomFields()
                );
            }
        }

        return $propertyGroupOptionEntity;
    }

    public function importCustomer(CustomerEntity $customerEntity, bool $mergeExistingCustomFieldsFirst = false): string
    {
        $this->customerModule->startTransaction();
        $this->customerModule->disableForeignKeys();

        if (!$this->hasValue($customerEntity)) {
            $customerId = $this->customerModule->selectCustomerId($customerEntity->getCustomerNumber());
            if (null === $customerId) {
                $customerId = $this->getRandomHex();
                $customerEntity->setId($customerId);
            } else {
                $customerEntity->setId($customerId);
                $this->mergeExistingCustomFields(
                    $customerEntity,
                    CustomerDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $customerEntity,
                CustomerDefinition::ENTITY_NAME,
                'id',
                $mergeExistingCustomFieldsFirst
            );
        }

        $customerBilling = $customerEntity->getDefaultBillingAddress();
        $customerShipping = $customerEntity->getDefaultShippingAddress();

        if (!$this->hasValue($customerBilling)) {
            $customerBillingId = $this->customerModule->selectCustomerAddressId($customerEntity->getId());
            if (null === $customerBillingId) {
                $customerBillingId = $this->getRandomHex();
                $customerBilling->setId($customerBillingId);
            } else {
                $customerBilling->setId($customerBillingId);
                $this->mergeExistingCustomFields(
                    $customerBilling,
                    CustomerAddressDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $customerBilling,
                CustomerAddressDefinition::ENTITY_NAME,
                'id',
                $mergeExistingCustomFieldsFirst
            );
        }

        if (null !== $customerShipping) {
            if (!$this->hasValue($customerShipping)) {
                $customerShippingId = $this->customerModule->selectCustomerAddressId(
                    $customerEntity->getId(),
                    $customerBilling->getId()
                );
                if (null === $customerShippingId) {
                    $customerShippingId = $this->getRandomHex();
                    $customerShipping->setId($customerShippingId);
                } else {
                    $customerShipping->setId($customerShippingId);
                    $this->mergeExistingCustomFields(
                        $customerShipping,
                        CustomerAddressDefinition::ENTITY_NAME,
                        'id',
                        $mergeExistingCustomFieldsFirst
                    );
                }
            } else {
                $this->mergeExistingCustomFields(
                    $customerShipping,
                    CustomerAddressDefinition::ENTITY_NAME,
                    'id',
                    $mergeExistingCustomFieldsFirst
                );
            }
        }

        if (!$this->hasValue($customerEntity, 'groupId')) {
            $customerEntity->setGroupId($this->customerModule->getDefaultCustomerGroupId());
        }

        if (!$this->hasValue($customerEntity, 'salutationId')) {
            $customerEntity->setSalutationId($this->customerModule->getDefaultSalutationId());
            $customerBilling->setSalutationId($this->customerModule->getDefaultSalutationId());
            if (null !== $customerShipping) {
                $customerShipping->setSalutationId($this->customerModule->getDefaultSalutationId());
            }
        }

        if (!$this->hasValue($customerEntity, 'defaultPaymentMethodId')) {
            $customerEntity->setDefaultPaymentMethodId($this->customerModule->getDefaultPaymentMethodId());
        }

        if (!$this->hasValue($customerEntity, 'salesChannelId')) {
            $customerEntity->setSalesChannelId($this->customerModule->getDefaultSalesChannel());
        }

        if (!$this->hasValue($customerEntity, 'languageId')) {
            $customerEntity->setLanguageId($this->getLanguageId());
        }

        if (!$this->hasValue($customerEntity, 'defaultPaymentMethodId')) {
            $customerEntity->setDefaultPaymentMethodId($this->customerModule->getDefaultPaymentMethodId());
        }

        foreach ([$customerShipping, $customerBilling] as $entity) {
            if (null === $entity) {
                continue;
            }
            if ($this->hasValue($entity, 'countryId')) {
                continue;
            }
            $entity->setCountryId($this->customerModule->getDefaultCountryId());
        }

        $this->customerModule->storeCustomer(
            $customerEntity->getId(),
            $customerEntity->getGroupId(),
            $customerEntity->getDefaultPaymentMethodId(),
            $customerEntity->getSalesChannelId(),
            $customerEntity->getLanguageId(),
            $customerBilling->getId(),
            null === $customerShipping ? $customerBilling->getId() : $customerShipping->getId(),
            $customerEntity->getCustomerNumber(),
            $customerEntity->getSalutationId(),
            $customerEntity->getEmail(),
            $customerEntity->getFirstName(),
            $customerEntity->getLastName(),
            $customerEntity->getActive(),
            $customerEntity->getGuest(),
            $customerEntity->getOrderCount(),
            $customerEntity->getCompany(),
            $customerEntity->getTitle(),
            null !== $customerEntity->getBirthday() ? $customerEntity->getBirthday()->format('Y-m-d') : null,
            $customerEntity->getCustomFields(),
            $customerEntity->getLastOrderDate(),
            $customerEntity->getRemoteAddress(),
            $customerEntity->getFirstLogin(),
            $customerEntity->getLastLogin(),
            $customerEntity->getPassword(),
            $customerEntity->getLegacyPassword(),
            $customerEntity->getLegacyEncoder(),
            $customerEntity->getLastPaymentMethodId(),
            $customerEntity->getDoubleOptInRegistration(),
            $customerEntity->getDoubleOptInEmailSentDate(),
            $customerEntity->getDoubleOptInConfirmDate(),
            $customerEntity->getHash(),
            $customerEntity->getAffiliateCode(),
            $customerEntity->getCampaignCode(),
            $customerEntity->getBoundSalesChannelId(),
            $customerEntity->getVatIds()
        );

        $this->customerModule->storeCustomerAddress(
            $customerBilling->getId(),
            $customerEntity->getId(),
            $customerBilling->getCountryId(),
            $customerBilling->getSalutationId(),
            $customerBilling->getFirstName(),
            $customerBilling->getLastName(),
            $customerBilling->getStreet(),
            $customerBilling->getZipcode(),
            $customerBilling->getCity(),
            $customerBilling->getPhoneNumber(),
            $customerBilling->getCompany(),
            $customerBilling->getDepartment(),
            $customerBilling->getCountryStateId(),
            $customerBilling->getTitle(),
            $customerBilling->getAdditionalAddressLine1(),
            $customerBilling->getAdditionalAddressLine2(),
            $customerBilling->getCustomFields()
        );

        if (null !== $customerShipping) {
            $this->customerModule->storeCustomerAddress(
                $customerShipping->getId(),
                $customerEntity->getId(),
                $customerShipping->getCountryId(),
                $customerShipping->getSalutationId(),
                $customerShipping->getFirstName(),
                $customerShipping->getLastName(),
                $customerShipping->getStreet(),
                $customerShipping->getZipcode(),
                $customerShipping->getCity(),
                $customerShipping->getPhoneNumber(),
                $customerShipping->getCompany(),
                $customerShipping->getDepartment(),
                $customerShipping->getCountryStateId(),
                $customerShipping->getTitle(),
                $customerShipping->getAdditionalAddressLine1(),
                $customerShipping->getAdditionalAddressLine2(),
                $customerShipping->getCustomFields()
            );
        }

        try {
            $this->customerModule->commitTransaction();
        } catch (\Exception $exception) {
            $this->customerModule->rollbackTransaction();
        }
        $this->customerModule->enableForeignKeys();

        return $customerEntity->getId();
    }

    public function importShippingMethod(
        ShippingMethodEntity $shippingMethodEntity,
        bool $mergeExistingCustomFieldsFirst = false
    ): string
    {
        $this->shippingMethodModule->startTransaction();
        $this->shippingMethodModule->disableForeignKeys();

        try {
            if (!$this->hasValue($shippingMethodEntity)) {
                $shippingMethodId = $this->shippingMethodModule->selectShippingMethodId(
                    $shippingMethodEntity->getName()
                );
                if (null === $shippingMethodId) {
                    $shippingMethodEntity->setId($this->getRandomHex());
                } else {
                    $shippingMethodEntity->setId($shippingMethodId);
                    $this->mergeExistingCustomFields(
                        $shippingMethodEntity,
                        ShippingMethodTranslationDefinition::ENTITY_NAME,
                        'shipping_method_id',
                        $mergeExistingCustomFieldsFirst,
                        $this->getLanguageId()
                    );
                }
            } else {
                $this->mergeExistingCustomFields(
                    $shippingMethodEntity,
                    ShippingMethodTranslationDefinition::ENTITY_NAME,
                    'shipping_method_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }

            $deliveryTime = $shippingMethodEntity->getDeliveryTime();
            if (!$this->hasValue($deliveryTime)) {
                $deliveryTimeId = $this->shippingMethodModule->selectDeliveryTimeId($deliveryTime->getName());
                if (null === $deliveryTimeId) {
                    $deliveryTime->setId($this->getRandomHex());
                } else {
                    $deliveryTime->setId($deliveryTimeId);
                    $this->mergeExistingCustomFields(
                        $deliveryTime,
                        DeliveryTimeTranslationDefinition::ENTITY_NAME,
                        'delivery_time_id',
                        $mergeExistingCustomFieldsFirst,
                        $this->getLanguageId()
                    );
                }
                $shippingMethodEntity->setDeliveryTimeId($deliveryTime->getId());
            } else {
                $this->mergeExistingCustomFields(
                    $deliveryTime,
                    DeliveryTimeTranslationDefinition::ENTITY_NAME,
                    'delivery_time_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }

            $this->shippingMethodModule->storeDeliveryTime(
                $deliveryTime->getId(),
                $deliveryTime->getMin(),
                $deliveryTime->getMax(),
                $deliveryTime->getUnit()
            );

            $this->shippingMethodModule->storeDeliveryTimeTranslation(
                $deliveryTime->getId(),
                $this->getLanguageId(),
                $deliveryTime->getName(),
                $deliveryTime->getCustomFields()
            );

            if (null !== $deliveryTime->getTranslations()) {
                foreach ($deliveryTime->getTranslations() as $translation) {
                    $this->mergeExistingCustomFields(
                        $deliveryTime,
                        DeliveryTimeTranslationDefinition::ENTITY_NAME,
                        'delivery_time_id',
                        $mergeExistingCustomFieldsFirst,
                        $translation->getLanguageId(),
                        $translation
                    );

                    $this->shippingMethodModule->storeDeliveryTimeTranslation(
                        $deliveryTime->getId(),
                        $translation->getLanguageId(),
                        $translation->getName(),
                        $translation->getCustomFields()
                    );
                }
            }

            $this->shippingMethodModule->storeShippingMethod(
                $shippingMethodEntity->getId(),
                $shippingMethodEntity->getActive(),
                $shippingMethodEntity->getAvailabilityRuleId(),
                $shippingMethodEntity->getMediaId(),
                $shippingMethodEntity->getDeliveryTimeId()
            );

            $this->shippingMethodModule->storeShippingMethodTranslation(
                $shippingMethodEntity->getId(),
                $this->getLanguageId(),
                $shippingMethodEntity->getName(),
                $shippingMethodEntity->getDescription(),
                $shippingMethodEntity->getCustomFields()
            );

            if (null !== $shippingMethodEntity->getTranslations()) {
                foreach ($shippingMethodEntity->getTranslations() as $translation) {
                    $this->mergeExistingCustomFields(
                        $shippingMethodEntity,
                        ShippingMethodTranslationDefinition::ENTITY_NAME,
                        'shipping_method_id',
                        $mergeExistingCustomFieldsFirst,
                        $translation->getLanguageId(),
                        $translation
                    );
                    $this->shippingMethodModule->storeShippingMethodTranslation(
                        $shippingMethodEntity->getId(),
                        $translation->getLanguageId(),
                        $translation->getName(),
                        $translation->getDescription(),
                        $translation->getCustomFields()
                    );
                }
            }

            foreach ($shippingMethodEntity->getPrices() as $price) {
                if (!$this->hasValue($price)) {
                    $priceId = $this->shippingMethodModule->selectShippingPriceId(
                        $shippingMethodEntity->getId(),
                        $price->getRuleId(),
                        $price->getCalculationRuleId(),
                        $price->getQuantityStart()
                    );

                    if (null === $priceId) {
                        $priceId = $this->getRandomHex();
                    }
                    $price->setId($priceId);
                }
                $this->shippingMethodModule->storeShippingPrice(
                    $price->getId(),
                    $shippingMethodEntity->getId(),
                    $price->getCurrencyPrice(),
                    $price->getRuleId(),
                    $price->getCalculationRuleId(),
                    $price->getQuantityStart(),
                    $price->getQuantityEnd(),
                    $price->getCalculation()
                );
            }

            $this->shippingMethodModule->commitTransaction();
        } catch (\Exception $exception) {
            $this->shippingMethodModule->rollbackTransaction();
            throw $exception;
        }

        return $shippingMethodEntity->getId();
    }

    public function importState(
        StateMachineStateEntity $stateMachineStateEntity,
        bool $mergeExistingCustomFieldsFirst = false
    ): string
    {
        if (!$this->hasValue($stateMachineStateEntity, 'stateMachineId')) {
            $stateMachineStateEntity->setStateMachineId($this->getStateMachineId(static::STATE_MACHINE_ORDER));
        }

        if (!$this->hasValue($stateMachineStateEntity)) {
            $stateId = $this->stateModule->selectStateId(
                $stateMachineStateEntity->getTechnicalName(),
                $stateMachineStateEntity->getStateMachineId()
            );
            if (null === $stateId) {
                $stateMachineStateEntity->setId($this->getRandomHex());
            } else {
                $stateMachineStateEntity->setId($stateId);
                $this->mergeExistingCustomFields(
                    $stateMachineStateEntity,
                    StateMachineStateTranslationDefinition::ENTITY_NAME,
                    'state_machine_state_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $stateMachineStateEntity,
                StateMachineStateTranslationDefinition::ENTITY_NAME,
                'state_machine_state_id',
                $mergeExistingCustomFieldsFirst,
                $this->getLanguageId()
            );
        }

        $this->stateModule->storeState(
            $stateMachineStateEntity->getId(),
            $stateMachineStateEntity->getStateMachineId(),
            $stateMachineStateEntity->getTechnicalName()
        );

        $this->stateModule->storeStateTranslation(
            $stateMachineStateEntity->getId(),
            $this->getLanguageId(),
            $stateMachineStateEntity->getName(),
            $stateMachineStateEntity->getCustomFields()
        );

        if (!empty($stateMachineStateEntity->getFromStateMachineTransitions())) {
            foreach ($stateMachineStateEntity->getFromStateMachineTransitions() as $fromStateMachineTransition) {
                $transitionId = $this->stateModule->selectStateTransitionId(
                    $fromStateMachineTransition->getActionName(),
                    $fromStateMachineTransition->getStateMachineId(),
                    $fromStateMachineTransition->getFromStateId(),
                    $stateMachineStateEntity->getId()
                );
                if (null === $transitionId) {
                    $transitionId = $this->getRandomHex();
                }
                $fromStateMachineTransition->setId($transitionId);

                $this->stateModule->storeStateTransition(
                    $fromStateMachineTransition->getId(),
                    $fromStateMachineTransition->getActionName(),
                    $fromStateMachineTransition->getStateMachineId(),
                    $fromStateMachineTransition->getFromStateId(),
                    $stateMachineStateEntity->getId(),
                    $fromStateMachineTransition->getCustomFields(),
                );
            }
        }

        if (!empty($stateMachineStateEntity->getToStateMachineTransitions())) {
            foreach ($stateMachineStateEntity->getToStateMachineTransitions() as $fromStateMachineTransition) {
                $transitionId = $this->stateModule->selectStateTransitionId(
                    $fromStateMachineTransition->getActionName(),
                    $fromStateMachineTransition->getStateMachineId(),
                    $stateMachineStateEntity->getId(),
                    $fromStateMachineTransition->getToStateId()
                );
                if (null === $transitionId) {
                    $transitionId = $this->getRandomHex();
                }
                $fromStateMachineTransition->setId($transitionId);

                $this->stateModule->storeStateTransition(
                    $fromStateMachineTransition->getId(),
                    $fromStateMachineTransition->getActionName(),
                    $fromStateMachineTransition->getStateMachineId(),
                    $stateMachineStateEntity->getId(),
                    $fromStateMachineTransition->getToStateId(),
                    $fromStateMachineTransition->getCustomFields(),
                );
            }
        }

        if ($this->hasValue($stateMachineStateEntity, 'translations')) {
            foreach ($stateMachineStateEntity->getTranslations() as $translation) {
                $this->mergeExistingCustomFields(
                    $stateMachineStateEntity,
                    StateMachineStateTranslationDefinition::ENTITY_NAME,
                    'state_machine_state_id',
                    $mergeExistingCustomFieldsFirst,
                    $translation->getLanguageId(),
                    $translation
                );

                $this->stateModule->storeStateTranslation(
                    $stateMachineStateEntity->getId(),
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getCustomFields()
                );
            }
        }

        return $stateMachineStateEntity->getId();
    }

    public function importPaymentMethod(
        PaymentMethodEntity $paymentMethodEntity,
        bool $mergeExistingCustomFieldsFirst = false
    ): string
    {
        if (!$this->hasValue($paymentMethodEntity)) {
            $stateId = $this->paymentMethodModule->selectPaymentMethodId($paymentMethodEntity->getName());
            if (null === $stateId) {
                $paymentMethodEntity->setId($this->getRandomHex());
            } else {
                $paymentMethodEntity->setId($stateId);
                $this->mergeExistingCustomFields(
                    $paymentMethodEntity,
                    PaymentMethodTranslationDefinition::ENTITY_NAME,
                    'payment_method_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $paymentMethodEntity,
                PaymentMethodTranslationDefinition::ENTITY_NAME,
                'payment_method_id',
                $mergeExistingCustomFieldsFirst,
                $this->getLanguageId()
            );
        }

        $this->paymentMethodModule->storePaymentMethod(
            $paymentMethodEntity->getId(),
            $paymentMethodEntity->getHandlerIdentifier(),
            $paymentMethodEntity->getPosition(),
            $paymentMethodEntity->getActive(),
            $paymentMethodEntity->getAvailabilityRuleId(),
            $paymentMethodEntity->getPluginId(),
            $paymentMethodEntity->getMediaId()
        );

        $this->paymentMethodModule->storePaymentMethodTranslation(
            $paymentMethodEntity->getId(),
            $this->getLanguageId(),
            $paymentMethodEntity->getName(),
            $paymentMethodEntity->getDescription(),
            $paymentMethodEntity->getCustomFields()
        );

        if (!empty($paymentMethodEntity->getTranslations())) {
            foreach ($paymentMethodEntity->getTranslations() as $translation) {
                $this->mergeExistingCustomFields(
                    $paymentMethodEntity,
                    PaymentMethodTranslationDefinition::ENTITY_NAME,
                    'payment_method_id',
                    $mergeExistingCustomFieldsFirst,
                    $translation->getLanguageId(),
                    $translation
                );
                $this->paymentMethodModule->storePaymentMethodTranslation(
                    $paymentMethodEntity->getId(),
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getDescription(),
                    $translation->getCustomFields()
                );
            }
        }

        return $paymentMethodEntity->getId();
    }

    public function getStateId(string $technicalName, string $getStateMachineId): ?string
    {
        return $this->stateModule->selectStateId($technicalName, $getStateMachineId);
    }

    public function getStateMachineId(string $name): ?string
    {
        $stateMachineId = $this->stateModule->selectStateMachineId($name);
        if (null === $stateMachineId) {
            return $this->salesChannelModule->selectSalesChannelTypeId(static::STATE_MACHINE_ORDER);
        }

        return $stateMachineId;
    }

    public function importCustomerGroup(
        CustomerGroupEntity $customerGroupEntity,
        bool $mergeExistingCustomFieldsFirst = false
    ): string
    {
        $this->customerModule->startTransaction();
        $this->customerModule->disableForeignKeys();

        try {
            if (!$this->hasValue($customerGroupEntity)) {
                $customerGroupId = $this->customerModule->getCustomerGroupId($customerGroupEntity->getName(), false);
                if (null === $customerGroupId) {
                    $customerGroupEntity->setId($this->getRandomHex());
                } else {
                    $customerGroupEntity->setId($customerGroupId);
                    $this->mergeExistingCustomFields(
                        $customerGroupEntity,
                        CustomerGroupTranslationDefinition::ENTITY_NAME,
                        'customer_group_id',
                        $mergeExistingCustomFieldsFirst,
                        $this->getLanguageId()
                    );
                }
            } else {
                $this->mergeExistingCustomFields(
                    $customerGroupEntity,
                    CustomerGroupTranslationDefinition::ENTITY_NAME,
                    'customer_group_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }

            $this->customerModule->storeCustomerGroup(
                $customerGroupEntity->getId(),
                $customerGroupEntity->getDisplayGross(),
                $customerGroupEntity->getRegistrationActive()
            );
            $this->customerModule->storeCustomerGroupTranslation(
                $customerGroupEntity->getId(),
                $this->getLanguageId(),
                $customerGroupEntity->getName(),
                $this->hasValue($customerGroupEntity, 'registrationTitle')
                    ? $customerGroupEntity->getRegistrationTitle()
                    : null,
                $this->hasValue($customerGroupEntity, 'registrationIntroduction')
                    ? $customerGroupEntity->getRegistrationIntroduction()
                    : null,
                $this->hasValue($customerGroupEntity, 'registrationOnlyCompanyRegistration')
                    ? $customerGroupEntity->getRegistrationOnlyCompanyRegistration()
                    : null,
                $this->hasValue($customerGroupEntity, 'registrationSeoMetaDescription')
                    ? $customerGroupEntity->getRegistrationSeoMetaDescription()
                    : null,
                $customerGroupEntity->getCustomFields()
            );

            if (null !== $customerGroupEntity->getTranslations()) {
                foreach ($customerGroupEntity->getTranslations() as $translation) {
                    $this->mergeExistingCustomFields(
                        $customerGroupEntity,
                        CustomerGroupTranslationDefinition::ENTITY_NAME,
                        'customer_group_id',
                        $mergeExistingCustomFieldsFirst,
                        $translation->getLanguageId(),
                        $translation
                    );

                    $this->customerModule->storeCustomerGroupTranslation(
                        $customerGroupEntity->getId(),
                        $translation->getLanguageId(),
                        $translation->getName(),
                        $translation->getRegistrationTitle(),
                        $translation->getRegistrationIntroduction(),
                        $translation->getRegistrationOnlyCompanyRegistration(),
                        $translation->getRegistrationSeoMetaDescription(),
                        $translation->getCustomFields()
                    );
                }
            }

            $this->customerModule->commitTransaction();
        } catch (\Exception $exception) {
            $this->customerModule->rollbackTransaction();
            throw $exception;
        }

        return $customerGroupEntity->getId();
    }

    public function importUnit(UnitEntity $unit): ?string
    {
        $unitId = $this->productModule->selectUnitId($unit->getName(), $this->getLanguageId());
        if (null === $unitId) {
            $unitId = $this->getRandomHex();
        }

        $this->productModule->storeUnit($unitId);
        $this->productModule->storeUnitTranslation(
            $unitId,
            $this->getLanguageId(),
            $unit->getShortCode(),
            $unit->getName(),
            $unit->getCustomFields()
        );

        if (null !== $unit->getTranslations()) {
            foreach ($unit->getTranslations() as $translation) {
                $this->productModule->storeUnitTranslation(
                    $unitId,
                    $translation->getLanguageId(),
                    $translation->getShortCode(),
                    $translation->getName(),
                    $translation->getCustomFields()
                );
            }
        }

        return $unitId;
    }

    public function importDeliveryTime(DeliveryTimeEntity $deliveryTime): ?string
    {
        $deliveryTimeId = $this->shippingMethodModule->selectDeliveryTimeId($deliveryTime->getName());
        if (null === $deliveryTimeId) {
            $deliveryTimeId = $this->getRandomHex();
        }
        $this->shippingMethodModule->storeDeliveryTime(
            $deliveryTimeId,
            $deliveryTime->getMin(),
            $deliveryTime->getMax(),
            $deliveryTime->getUnit()
        );

        $this->shippingMethodModule->storeDeliveryTimeTranslation(
            $deliveryTimeId,
            $this->getLanguageId(),
            $deliveryTime->getName(),
            $deliveryTime->getCustomFields()
        );

        if (null !== $deliveryTime->getTranslations()) {
            foreach ($deliveryTime->getTranslations() as $translation) {
                $this->shippingMethodModule->storeDeliveryTimeTranslation(
                    $deliveryTimeId,
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getCustomFields()
                );
            }
        }

        return $deliveryTimeId;
    }

    public function importSalesChannel(
        SalesChannelEntity $salesChannelEntity,
        bool $mergeExistingCustomFieldsFirst = false
    ): SalesChannelEntity
    {
        if (!$this->hasValue($salesChannelEntity)) {
            $salesChannelId = $this->salesChannelModule->selectSalesChannelId($salesChannelEntity->getName());
            if (null === $salesChannelId) {
                $salesChannelEntity->setId($this->getRandomHex());
            } else {
                $salesChannelEntity->setId($salesChannelId);
                $this->mergeExistingCustomFields(
                    $salesChannelEntity,
                    SalesChannelTranslationDefinition::ENTITY_NAME,
                    'sales_channel_id',
                    $mergeExistingCustomFieldsFirst,
                    $this->getLanguageId()
                );
            }
        } else {
            $this->mergeExistingCustomFields(
                $salesChannelEntity,
                SalesChannelTranslationDefinition::ENTITY_NAME,
                'sales_channel_id',
                $mergeExistingCustomFieldsFirst,
                $this->getLanguageId()
            );
        }

        if (!$this->hasValue($salesChannelEntity, 'typeId')) {
            $salesChannelEntity->setTypeId($this->getSalesChannelTypeId(static::DEFAULT_SALES_CHANNEL_TYPE));
        }

        if (!$this->hasValue($salesChannelEntity, 'languageId')) {
            $salesChannelEntity->setLanguageId($this->getLanguageId());
        }

        if (
            empty($salesChannelEntity->getPaymentMethodIds())
            && !empty($salesChannelEntity->getPaymentMethods())
        ) {
            $paymentMethodsIds = [];
            foreach ($salesChannelEntity->getPaymentMethods() as $paymentMethod) {
                $paymentMethodsIds[] = $paymentMethod->getId();
            }
            $salesChannelEntity->setPaymentMethodIds($paymentMethodsIds);
        }

        $this->salesChannelModule->storeSalesChannel(
            $salesChannelEntity->getId(),
            $salesChannelEntity->getTypeId(),
            $salesChannelEntity->getLanguageId(),
            $salesChannelEntity->getCurrencyId(),
            $salesChannelEntity->getCustomerGroupId(),
            $salesChannelEntity->getCountryId(),
            $salesChannelEntity->getNavigationCategoryId(),
            $salesChannelEntity->getFooterCategoryId(),
            $salesChannelEntity->getServiceCategoryId(),
            $salesChannelEntity->getPaymentMethodId(),
            $salesChannelEntity->getShippingMethodId(),
            $salesChannelEntity->getMailHeaderFooterId(),
            $salesChannelEntity->getAccessKey(),
            $salesChannelEntity->getShortName(),
            $salesChannelEntity->getConfiguration(),
            $salesChannelEntity->getMaintenanceIpWhitelist(),
            $salesChannelEntity->getPaymentMethodIds()
        );

        $this->salesChannelModule->storeSalesChannelTranslation(
            $salesChannelEntity->getId(),
            $this->getLanguageId(),
            $salesChannelEntity->getName(),
            $salesChannelEntity->getCustomFields()
        );

        if (!empty($salesChannelEntity->getTranslations())) {
            foreach ($salesChannelEntity->getTranslations() as $translation) {
                $this->mergeExistingCustomFields(
                    $salesChannelEntity,
                    SalesChannelTranslationDefinition::ENTITY_NAME,
                    'sales_channel_id',
                    $mergeExistingCustomFieldsFirst,
                    $translation->getLanguageId(),
                    $translation
                );
                $this->salesChannelModule->storeSalesChannelTranslation(
                    $salesChannelEntity->getId(),
                    $translation->getLanguageId(),
                    $translation->getName(),
                    $translation->getCustomFields()
                );
            }
        }

        if (!empty($salesChannelEntity->getDomains())) {
            foreach ($salesChannelEntity->getDomains() as $domain) {
                if (!$this->hasValue($domain)) {
                    $domainId = $this->salesChannelModule->selectDomainId($domain->getUrl());
                    if (null === $domainId) {
                        $domain->setId($this->getRandomHex());
                    } else {
                        $domain->setId($domainId);
                        $this->mergeExistingCustomFields(
                            $domain,
                            SalesChannelDomainDefinition::ENTITY_NAME,
                            'id',
                            $mergeExistingCustomFieldsFirst
                        );
                    }
                } else {
                    $this->mergeExistingCustomFields(
                        $domain,
                        SalesChannelDomainDefinition::ENTITY_NAME,
                        'id',
                        $mergeExistingCustomFieldsFirst
                    );
                }

                if (!$this->hasValue($domain, 'snippetSetId')) {
                    $domain->setSnippetSetId($this->getSnippetSetId(static::DEFAULT_SNIPPET_SET));
                }

                $this->salesChannelModule->storeSalesChannelDomain(
                    $domain->getId(),
                    $salesChannelEntity->getId(),
                    $domain->getLanguageId(),
                    $domain->getUrl(),
                    $domain->getCurrencyId(),
                    $domain->getSnippetSetId(),
                    $domain->getCustomFields()
                );
            }
        }

        if (!empty($salesChannelEntity->getCurrencies())) {
            $this->salesChannelModule->resetSalesChannelCurrencies($salesChannelEntity->getId());
            foreach ($salesChannelEntity->getCurrencies() as $currency) {
                $this->salesChannelModule->storeSalesChannelCurrency($salesChannelEntity->getId(), $currency->getId());
            }
        }

        if (!empty($salesChannelEntity->getCountries())) {
            $this->salesChannelModule->resetSalesChannelCountries($salesChannelEntity->getId());
            foreach ($salesChannelEntity->getCountries() as $country) {
                $this->salesChannelModule->storeSalesChannelCountry($salesChannelEntity->getId(), $country->getId());
            }
        }

        if (!empty($salesChannelEntity->getLanguages())) {
            $this->salesChannelModule->resetSalesChannelLanguages($salesChannelEntity->getId());
            foreach ($salesChannelEntity->getLanguages() as $language) {
                $this->salesChannelModule->storeSalesChannelLanguage($salesChannelEntity->getId(), $language->getId());
            }
        }

        if (!empty($salesChannelEntity->getPaymentMethods())) {
            $this->salesChannelModule->resetSalesChannelPaymentMethods($salesChannelEntity->getId());
            foreach ($salesChannelEntity->getPaymentMethods() as $paymentMethod) {
                $this->salesChannelModule->storeSalesChannelPaymentMethod(
                    $salesChannelEntity->getId(),
                    $paymentMethod->getId()
                );
            }
        }

        if (!empty($salesChannelEntity->getShippingMethod())) {
            $this->salesChannelModule->resetSalesChannelShippingMethods($salesChannelEntity->getId());
            foreach ($salesChannelEntity->getShippingMethod() as $shippingMethod) {
                $this->salesChannelModule->storeSalesChannelShippingMethod(
                    $salesChannelEntity->getId(),
                    $shippingMethod->getId()
                );
            }
        }

        return $salesChannelEntity;
    }

    public function getSalesChannelTypeId(string $name): ?string
    {
        $typeId = $this->salesChannelModule->selectSalesChannelTypeId($name);
        if (null === $typeId) {
            return $this->salesChannelModule->selectSalesChannelTypeId(static::DEFAULT_SALES_CHANNEL_TYPE);
        }

        return $typeId;
    }

    public function getSnippetSetId(string $localeIso): ?string
    {
        $snippetSetId = $this->salesChannelModule->selectSnippetSetId($localeIso);
        if (null === $snippetSetId) {
            return $this->salesChannelModule->selectSalesChannelTypeId(static::DEFAULT_SNIPPET_SET);
        }

        return $snippetSetId;
    }

    public function getRuleId(string $name, ?RuleEntity $ruleEntity = null): ?string
    {
        $ruleId = $this->ruleModule->selectRuleId($name);
        if (null !== $ruleId) {
            return $ruleId;
        }

        if (null !== $ruleEntity) {
            $this->importRule($ruleEntity);

            return $ruleEntity->getId();
        }

        return null;
    }

    public function importRule(RuleEntity $ruleEntity): string
    {
        if (!$this->hasValue($ruleEntity)) {
            $ruleId = $this->getRandomHex();
            $ruleEntity->setId($ruleId);
        }

        if (!$this->hasValue($ruleEntity, 'invalid')) {
            $ruleEntity->setInvalid(false);
        }

        if (!$this->hasValue($ruleEntity, 'priority')) {
            $ruleEntity->setPriority(0);
        }

        $this->ruleModule->storeRule(
            $ruleEntity->getId(),
            $ruleEntity->getName(),
            $ruleEntity->getPayload(),
            $ruleEntity->getDescription(),
            $ruleEntity->getPriority(),
            $ruleEntity->getAreas(),
            $ruleEntity->getModuleTypes(),
            $ruleEntity->getCustomFields(),
            $ruleEntity->isInvalid()
        );

        $this->ruleModule->resetRuleConditions($ruleEntity->getId());
        foreach ($ruleEntity->getConditions() as $conditionEntity) {
            if (!$this->hasValue($conditionEntity)) {
                $conditionId = $this->getRandomHex();
                $conditionEntity->setId($conditionId);
            }

            if (!$this->hasValue($conditionEntity, 'position')) {
                $conditionEntity->setPosition(0);
            }

            $this->ruleModule->storeRuleCondition(
                $conditionEntity->getId(),
                $ruleEntity->getId(),
                $conditionEntity->getType(),
                $conditionEntity->getValue(),
                $conditionEntity->getPosition(),
                $conditionEntity->getParentId(),
                $conditionEntity->getScriptId(),
                $conditionEntity->getCustomFields()
            );
        }

        return $ruleEntity->getId();
    }

    public function importTag(TagEntity $tagEntity): string
    {
        if (!$this->hasValue($tagEntity)) {
            $tagId = $this->tagModule->selectTagId($tagEntity->getName());
            if (null === $tagId) {
                $tagId = $this->getRandomHex();
            }
            $tagEntity->setId($tagId);
        }

        $this->tagModule->storeTag(
            $tagEntity->getId(),
            $tagEntity->getName()
        );

        return $tagEntity->getId();
    }

    public function importNewsletterRecipient(NewsletterRecipientEntity $newsletterRecipientEntity): string
    {
        if (!$this->hasValue($newsletterRecipientEntity)) {
            $recipientId = $this
                ->newsletterRecipientModule
                ->selectNewsletterRecipientId($newsletterRecipientEntity->getEmail())
            ;
            if (null === $recipientId) {
                $recipientId = $this->getRandomHex();
            }
            $newsletterRecipientEntity->setId($recipientId);
        }

        if (!$this->hasValue($newsletterRecipientEntity, 'status')) {
            $newsletterRecipientEntity->setStatus(NewsletterSubscribeRoute::STATUS_DIRECT);
        }

        if (!$this->hasValue($newsletterRecipientEntity, 'hash')) {
            $newsletterRecipientEntity->setHash(Uuid::randomHex());
        }

        $this->newsletterRecipientModule->storeNewsletterRecipient(
            $newsletterRecipientEntity->getId(),
            $newsletterRecipientEntity->getEmail(),
            $newsletterRecipientEntity->getSalesChannelId(),
            $newsletterRecipientEntity->getHash(),
            $newsletterRecipientEntity->getStatus(),
            $newsletterRecipientEntity->getTitle(),
            $newsletterRecipientEntity->getFirstName(),
            $newsletterRecipientEntity->getLastName(),
            $newsletterRecipientEntity->getStreet(),
            $newsletterRecipientEntity->getZipCode(),
            $newsletterRecipientEntity->getCity(),
            $newsletterRecipientEntity->getSalutationId(),
            $newsletterRecipientEntity->getLanguageId(),
            $newsletterRecipientEntity->getCustomFields(),
            $newsletterRecipientEntity->getConfirmedAt(),
            $newsletterRecipientEntity->getCreatedAt(),
        );

        if (null !== $newsletterRecipientEntity->getTags()) {
            $this->newsletterRecipientModule->resetNewsletterRecipientTag($newsletterRecipientEntity->getId());
            foreach ($newsletterRecipientEntity->getTags() as $tag) {
                $this->newsletterRecipientModule->storeNewsletterRecipientTag(
                    $newsletterRecipientEntity->getId(),
                    $tag->getId(),
                );
            }
        }

        return $newsletterRecipientEntity->getId();
    }

    public function importProductCrossSelling(ProductCrossSellingEntity $productCrossSelling): string
    {
        if (!$this->hasValue($productCrossSelling)) {
            $crossSelling = $this->productModule->selectProductCrossSellingByName(
                $productCrossSelling->getName()
            );
            if (null === $crossSelling) {
                $crossSellingId = $this->getRandomHex();
            } else {
                $crossSellingId = $crossSelling['id'];
            }
            $productCrossSelling->setId($crossSellingId);
        }

        if (!$this->hasValue($productCrossSelling, 'active')) {
            $productCrossSelling->setActive(true);
        }

        if (!$this->hasValue($productCrossSelling, 'type')) {
            $productCrossSelling->setType(ProductCrossSellingDefinition::TYPE_PRODUCT_LIST);
        }

        if (!$this->hasValue($productCrossSelling, 'sortBy')) {
            $productCrossSelling->setSortBy(ProductCrossSellingDefinition::SORT_BY_NAME);
        }

        if (!$this->hasValue($productCrossSelling, 'sortDirection')) {
            $productCrossSelling->setSortDirection('ASC');
        }

        if (!$this->hasValue($productCrossSelling, 'limit')) {
            $productCrossSelling->setLimit(24);
        }

        $this->productModule->storeProductCrossSelling(
            $productCrossSelling->getId(),
            $productCrossSelling->getProductId(),
            $productCrossSelling->getProductStreamId(),
            $productCrossSelling->getPosition(),
            $productCrossSelling->isActive(),
            $productCrossSelling->getType(),
            $productCrossSelling->getSortBy(),
            $productCrossSelling->getSortDirection(),
            $productCrossSelling->getLimit()
        );

        $this->productModule->storeProductCrossSellingTranslation(
            $productCrossSelling->getId(),
            $productCrossSelling->getName(),
            $this->getLanguageId()
        );

        if (null !== $productCrossSelling->getTranslations()) {
            foreach ($productCrossSelling->getTranslations() as $translation) {
                $this->productModule->storeProductCrossSellingTranslation(
                    $productCrossSelling->getId(),
                    $translation->getName(),
                    $translation->getLanguageId()
                );
            }
        }

        $this->productModule->resetProductCrossSellings($productCrossSelling->getId());
        foreach ($productCrossSelling->getAssignedProducts() as $assignedProduct) {
            $this->productModule->storeProductCrossSellingAssignedProduct(
                $productCrossSelling->getId(),
                $assignedProduct->getProductId(),
                $assignedProduct->getPosition()
            );
        }

        return $productCrossSelling->getId();
    }

    public function uploadImage(
        string $imageUrl,
        string $album = 'product',
        ?string $extension = null,
        bool $overwrite = false
    ): ?string
    {
        $isLocalFile = str_starts_with($imageUrl, '/');
        $fileParts = explode('/', $imageUrl);
        try {
            $extension ??= $this->getImageType($imageUrl);
        } catch (\Exception $exception) {
            return null;
        }
        $fileName = str_replace(['.' . $extension, ' '], '', urldecode(array_pop($fileParts)));
        $mediaId = $this->getMediaId($fileName, $extension);
        if (null !== $mediaId && $overwrite) {
            $mediaEntity = new MediaEntity();
            $mediaEntity->setId($mediaId);
            $this->mediaHelper->deleteMedia($mediaEntity, true);
        }
        if (null === $mediaId || $overwrite) {
            try {
                if ($isLocalFile) {
                    $content = file_get_contents($imageUrl);
                } else {
                    $options = [
                        \CURLOPT_RETURNTRANSFER => true,     // return web page
                        \CURLOPT_HEADER         => false,    // do not return headers
                        \CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                        \CURLOPT_USERAGENT      => 'spider', // who am i
                        \CURLOPT_AUTOREFERER    => true,     // set referer on redirect
                        \CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
                        \CURLOPT_TIMEOUT        => 120,      // timeout on response
                        \CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
                        \CURLOPT_HTTP_VERSION   => \CURL_HTTP_VERSION_2,
                    ];
                    $ch = curl_init($imageUrl);
                    curl_setopt_array($ch, $options);
                    $content = curl_exec($ch);
                    curl_close($ch);
                }

                if ($content) {
                    $mediaId = $this->mediaHelper->saveMediaFromBlob($content, $extension, $fileName, $album);
                }
            } catch (\Exception $exception) {
                return null;
            }
        }

        return $mediaId;
    }

    private function getImageType(string $imagePath): string
    {
        $validExtensions = [
            \IMAGETYPE_GIF     => 'gif',
            \IMAGETYPE_JPEG    => 'jpeg',
            \IMAGETYPE_PNG     => 'png',
            \IMAGETYPE_SWF     => 'swf',
            \IMAGETYPE_PSD     => 'psd',
            \IMAGETYPE_BMP     => 'bmp',
            \IMAGETYPE_TIFF_II => 'tiff',
            \IMAGETYPE_TIFF_MM => 'tiff',
            \IMAGETYPE_JPC     => 'jpc',
            \IMAGETYPE_JP2     => 'jp2',
            \IMAGETYPE_JPX     => 'jpx',
            \IMAGETYPE_JB2     => 'jb2',
            \IMAGETYPE_SWC     => 'swc',
            \IMAGETYPE_IFF     => 'iff',
            \IMAGETYPE_WBMP    => 'wbmp',
            \IMAGETYPE_XBM     => 'xbm',
            \IMAGETYPE_ICO     => 'ico',
            \IMAGETYPE_WEBP    => 'webp',
            \IMAGETYPE_AVIF    => 'avif',
        ];

        if (false !== strpos($imagePath, '.svg')) {
            return 'svg';
        }

        if (false !== strpos($imagePath, '.jpg')) {
            return 'jpg';
        }

        if (false !== strpos($imagePath, '.png')) {
            return 'png';
        }

        if (isset($validExtensions[@exif_imagetype($imagePath)])) {
            if (\IMAGETYPE_JPEG === @exif_imagetype($imagePath) && false === strpos($imagePath, '.jpeg')) {
                return 'jpg';
            }

            return $validExtensions[@exif_imagetype($imagePath)];
        }

        throw new \Exception('Image type not supported ' . $imagePath);
    }

    public function buildCalculatedPrice(
        float $price,
        int $quantity,
        float $taxRate,
        bool $isNet = false
    ): CalculatedPrice
    {
        $calculatedTax = new CalculatedTax(
            $isNet ? 0 : $price - ($price / (1 + ($taxRate / 100))),
            $taxRate,
            $price
        );

        $taxRule = new TaxRule($taxRate);

        return new CalculatedPrice(
            $price,
            $price * $quantity,
            new CalculatedTaxCollection([$calculatedTax]),
            new TaxRuleCollection([$taxRule])
        );
    }

    public function getRandomHex(): string
    {
        return Uuid::randomHex();
    }

    public function setDefaultLanguageId(string $languageId): void
    {
        $this->defaultLanguageId = $languageId;
        $this->productModule->setDefaultLanguageId($languageId);
        $this->categoryModule->setDefaultLanguageId($languageId);
        $this->propertyModule->setDefaultLanguageId($languageId);
        $this->paymentMethodModule->setDefaultLanguageId($languageId);
        $this->shippingMethodModule->setDefaultLanguageId($languageId);
        $this->manufacturerModule->setDefaultLanguageId($languageId);
        $this->ruleModule->setDefaultLanguageId($languageId);
        $this->salesChannelModule->setDefaultLanguageId($languageId);
        $this->stateModule->setDefaultLanguageId($languageId);
    }

    public function getLanguageId(): string
    {
        if (
            null !== $this->defaultLanguageId
        ) {
            $this->productModule->setDefaultLanguageId($this->defaultLanguageId);

            return $this->productModule->getDefaultLanguageId();
        }
        if (
            self::DEFAULT_LANGUAGE === $this->defaultLanguage
        ) {
            return $this->productModule->getDefaultLanguageId();
        }

        return $this->productModule->getLanguageIdByName($this->getDefaultLanguage());
    }

    public function getCountryIdByIso(string $iso): string
    {
        return $this->customerModule->getCountryId($iso);
    }

    public function getCustomerGroupId(string $name): string
    {
        return $this->customerModule->getCustomerGroupId($name);
    }

    private function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    private function mergeExistingCustomFields(
        Entity $entity,
        string $table,
        string $column,
        bool $mergeExistingFirst = false,
        ?string $languageId = null,
        ?Entity $translation = null
    ): void
    {
        if (!$this->productModule->isCustomFieldMergeEnabled()) {
            return;
        }

        if ($this->enableCustomFieldMergeExistingFirst) {
            $mergeExistingFirst = true;
        }

        $entityToSetCustomFields = $translation ?? $entity;
        $persistingCustomFields = $entityToSetCustomFields->getCustomFields() ?? [];
        if ($mergeExistingFirst) {
            $persistingCustomFields = array_merge(
                $this->productModule->selectCustomFields(
                    $table,
                    $column,
                    $entity->getId(),
                    $languageId
                ),
                $persistingCustomFields
            );
        } else {
            $persistingCustomFields = array_merge(
                $persistingCustomFields,
                $this->productModule->selectCustomFields(
                    $table,
                    $column,
                    $entity->getId(),
                    $languageId
                )
            );
        }

        $entityToSetCustomFields->setCustomFields($persistingCustomFields);
    }

    private function hasValue(Entity $entity, string $valueName = 'id'): bool
    {
        $entityArray = $entity->jsonSerialize();

        return isset($entityArray[$valueName]) && null !== $entityArray[$valueName];
    }
}
