<?php

namespace NewsletterSendinblue\Controller\Api;

use Exception;
use Monolog\Logger;
use NewsletterSendinblue\Model\Field;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationEntity;
use Shopware\Core\System\CustomField\CustomFieldEntity;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Tax\TaxEntity;
use Shopware\Storefront\Framework\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaType\MediaType;

#[Route(defaults: ['_routeScope' => ['api']])]
class ProductFieldController extends AbstractController
{

    /** @var Logger $logger */
    private $logger;

    private $customFieldRepository;

    /** @var EntityRepository $productFieldRepository */
    private $productRepository;

    /** @var UrlGeneratorInterface $router */
    private $router;

    /**
     * @param Logger $logger
     * @param DefinitionInstanceRegistry $definitionInstanceRegistry
     * @param CustomFieldSetDefinition $customFieldSetDefinition
     * @param ProductDefinition $productDefinition
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        Logger $logger,
        DefinitionInstanceRegistry $definitionInstanceRegistry,
        CustomFieldSetDefinition $customFieldSetDefinition,
        ProductDefinition $productDefinition,
        UrlGeneratorInterface $router
    ) {
        $this->logger = $logger;
        $this->customFieldRepository = $definitionInstanceRegistry->getRepository($customFieldSetDefinition->getEntityName());
        $this->productRepository = $definitionInstanceRegistry->getRepository($productDefinition->getEntityName());
        $this->router = $router;
    }

    /**
     * @Route("/api/v{version}/n2g/products/fields", name="api.v.action.n2g.getProductFields", methods={"GET"})
     * @Route("/api/n2g/products/fields", name="api.action.n2g.getProductFields", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProductAttributes(Request $request, Context $context): JsonResponse
    {
        $data = [];
        $productFields = array_merge($this->getProductDefaultFields(), $this->_getProductCustomFields());

        /** @var Field $field */
        foreach ($productFields as $field) {
            if ($field->getId() === 'customFields') {
                continue;
            }

            $data[] = [
                Field::FIELD_ID => $field->getId(),
                Field::FIELD_NAME => $field->getName(),
                Field::FIELD_TYPE => $field->getType(),
                Field::FIELD_DESCRIPTION => $field->getDescription()
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    private function getProductDefaultFields(): array
    {
        return [
            new Field('id'),
            new Field('name'),
            new Field('productNumber'),
            new Field('description'),
            new Field('url'),
            new Field('link'),
            new Field('price', Field::DATATYPE_FLOAT),
            new Field('media', Field::DATATYPE_ARRAY),
            new Field('image'),
            new Field('variants', Field::DATATYPE_ARRAY),
            new Field('categories', Field::DATATYPE_ARRAY),
            new Field('discount', Field::DATATYPE_FLOAT)
        ];
    }

    private function _getProductCustomFields()
    {
        $fields = [];

        try {
            //get product custom fields
            $criteria = new Criteria();
            $criteria->addAssociation('customFields');
            $criteria->addAssociation('relations');
            $result = $this->customFieldRepository->search($criteria, Context::createDefaultContext());

            /** @var CustomFieldSetEntity $customFieldSetEntity */
            foreach ($result->getElements() as $customFieldSetEntity) {
                /** @var CustomFieldSetRelationEntity $relation */
                foreach ($customFieldSetEntity->getRelations()->getElements() as $relation) {
                    if ($relation->getEntityName() === 'product') {
                        /** @var CustomFieldEntity $customField */
                        foreach ($customFieldSetEntity->getCustomFields() as $customField) {
                            $fieldName = $customFieldSetEntity->getName() . '__' . $customField->getName();
                            $translated = $customField->getTranslated();
                            $fieldDescription = !empty($customField->getTranslated()) ? reset($translated) : '';
                            $fields[] = new Field(
                                'sib_' . $customField->getName(),
                                DatatypeHelper::convertToSendinblueDatatype($customField->getType()),
                                $fieldName,
                                $fieldDescription
                            );
                        }
                    }

                }
            }

        } catch (\Exception $exception) {
            //
        }

        return $fields;
    }

    public function prepareProductAttributes(ProductEntity $productEntity): array
    {
        $preparedProductList = [];

        /** @var Field $field */
        foreach ($this->getProductDefaultFields() as $field) {
            $fieldId = $field->getId();

            if ($productEntity->has($fieldId)) {
                $attribute = $productEntity->get($fieldId);

                if (is_string($attribute) || is_numeric($attribute) || is_bool($attribute)) {
                    $preparedProductList[$fieldId] = $attribute;
                } elseif (is_null($attribute)) {
                    $preparedProductList[$fieldId] = '';
                } elseif ($attribute instanceof PriceCollection) {
                    $priceArr = $this->preparePriceEntity($attribute);
                    $preparedProductList[$fieldId] = $priceArr['gross'];
                    $preparedProductList['netprice'] = $priceArr['net'];
                    $preparedProductList['oldPrice'] = $priceArr['listPriceGross'];
                    $preparedProductList['oldNetprice'] = $priceArr['listPriceNet'];
                } elseif ($attribute instanceof CategoryCollection) {
                    $preparedProductList[$fieldId] = $this->prepareCategoryEntity($attribute);
                } elseif ($attribute instanceof ProductMediaCollection) {
                    $cover = $productEntity->has('cover') ? $productEntity->get('cover') : null;
                    $preparedProductList['image'] = $this->prepareMediaEntity($attribute, $cover);
                }
            } elseif ($fieldId === 'link') {
                $preparedProductList['url'] = sprintf('%s/', rtrim(getenv('APP_URL'), '/'));
                $preparedProductList[$fieldId] = sprintf('detail/%s', $productEntity->getId());
                $preparedProductList['fullUrl'] = sprintf('%s/detail/%s', rtrim(getenv('APP_URL'), '/'), $productEntity->getId());
            } elseif ($fieldId === 'variants') {
                $preparedProductList['variants'] =  $this->prepareVariants($productEntity);
            }
        }

        return $preparedProductList;
    }

    private function translateProduct(ProductEntity $productEntity, $languageId = null)
    {
        /** @var ProductTranslationCollection $productTranslations */
        $productTranslations = $productEntity->getTranslations();

        /** @var ProductTranslationEntity $productTranslation */
        if (!empty($languageId)) {
            foreach ($productTranslations as $productTranslation) {
                if ($productTranslation->getLanguageId() == $languageId) {
                    $translation = $productTranslation;
                }
            }
        }

        if (empty($translation)) {
            $translation = $productTranslations->first();
        }

        $TranslatedCustomFields = $translation->getCustomFields();
        //$productEntity->setAdditionalText($translation->getAdditionalText());
        $productEntity->setName($translation->getName());
        $productEntity->setDescription($translation->getDescription());
        $productEntityCustomFields = $productEntity->getCustomFields();

        if (is_array($productEntityCustomFields) && is_array($TranslatedCustomFields)) {
            foreach ($productEntityCustomFields as $key => $value) {
                if (!isset($TranslatedCustomFields)) {
                    $TranslatedCustomFields[] = $productEntityCustomFields[$key];
                }
            }

            $productEntity->setCustomFields($TranslatedCustomFields);
        }

        return $productEntity;
    }

    private function preparePriceEntity(PriceCollection $price): array
    {
        $price = $price->first();

        $priceData = [
            'net' => round($price->getNet(), 2),
            'gross' => round($price->getGross(), 2),
            'listPriceNet' => '',
            'listPriceGross' => '',
        ];
        if ($price->getListPrice()) {
            $priceData['listPriceNet'] = $price->getListPrice()->getNet();
            $priceData['listPriceGross'] = $price->getListPrice()->getGross();
        }

        return $priceData;
    }

    private function prepareMediaEntity(ProductMediaCollection $attribute, ?ProductMediaEntity $cover): string
    {
        if ($cover instanceof ProductMediaEntity
            && $cover->getMedia() instanceof MediaEntity
            && $cover->getMedia()->getMediaType() instanceof MediaType
            && $cover->getMedia()->getMediaType()->getName() === 'IMAGE'
        ) {
            return $cover->getMedia()->getUrl();
        }

        /** @var ProductMediaEntity $mediaEntity */
        foreach ($attribute->getElements() as $mediaEntity) {
            if ($mediaEntity->getMedia() instanceof MediaEntity
                && $mediaEntity->getMedia()->getMediaType() instanceof MediaType
                && $mediaEntity->getMedia()->getMediaType()->getName() === 'IMAGE'
            ) {
                return $mediaEntity->getMedia()->getUrl();
            }
        }

        return '';
    }

    private function prepareCategoryEntity(CategoryCollection $attribute): array
    {
        $categories = [];

        /** @var CategoryEntity $categoryEntity */
        foreach ($attribute->getElements() as $categoryEntity) {
           $categories[] = [
               'id'   => $categoryEntity->getId(),
               'name' => $categoryEntity->getName(),
               'fullUrl' => $this->router->generate('frontend.navigation.page', ['navigationId' =>$categoryEntity->getId()], Router::ABSOLUTE_URL)
            ];
        }

        return $categories;
    }

    private function prepareVariants(ProductEntity $attribute): array
    {
        $variants = [];
        $criteria = new Criteria();
        $criteria->addAssociation('options.group');
        $criteria->addFilter(new EqualsFilter('active', 1));
        $criteria->addFilter(new EqualsFilter('parentId', $attribute->getId()));

        try {
            $productVariants = $this->productRepository->search($criteria, Context::createDefaultContext());

            if (!$productVariants->getTotal()) {
                return $variants;
            }

            /** @var ProductEntity $variantProduct */
            foreach ($productVariants as $productVariant) {
                $variantArray['id'] = $productVariant->getId();
                $variantArray['productNumber'] = $productVariant->getProductNumber();
                if ($attribute->getPrice()) {
                    $priceArr = $this->preparePriceEntity($attribute->getPrice());
                    $variantArray['price'] = $priceArr['gross'];
                    $variantArray['netprice'] = $priceArr['net'];
                    $variantArray['oldPrice'] = $priceArr['listPriceGross'];
                    $variantArray['oldNetprice'] = $priceArr['listPriceNet'];
                } else {
                    $variantArray['netprice'] = '';
                    $variantArray['price'] = '';
                    $variantArray['oldPrice'] = '';
                    $variantArray['oldNetprice'] = '';
                }
                foreach ($productVariant->getOptions() as $option) {
                    if ($option->getGroup()) {
                        $variantArray[$option->getGroup()->getName()] = $option->getName();
                    }
                }
                $variants[] = $variantArray;
            }
        } catch (Exception $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
            return $variants;
        }

        return $variants;
    }

    private function prepareEntity(Entity $attribute): array
    {
        $entityAttributes = [];

        if ($attribute instanceof TaxEntity) {
            $entityAttributes['taxRate'] = $attribute->getTaxRate() ;
        } elseif ($attribute instanceof ProductManufacturerEntity) {
            $entityAttributes['name'] = $attribute->getName() ?: '';
        }

        return $entityAttributes;
    }
}
