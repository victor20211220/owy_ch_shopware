<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\DataAbstractionLayer\VariantListingConfig;
use Shopware\Core\Content\Product\SearchKeyword\AnalyzedKeyword;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tag\TagCollection;

final class ProductModule extends BaseModule
{
    private array $productIdCache = [];
    private array $taxIdCache = [];
    private array $currencyCache = [];
    private array $configCache = [];

    public function selectProductId(string $productNumber): ?string
    {
        $result = '';
        if (!isset($this->productIdCache[$productNumber])) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(id)) FROM product WHERE product_number = :productNumber
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('productNumber', $productNumber, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->productIdCache[$productNumber] = $result;
            }
        }

        return $this->isCacheEnabled() ? $this->productIdCache[$productNumber] : (string) $result;
    }

    public function selectProductIdByName(string $productName): ?string
    {
        $result = '';
        if (!isset($this->productIdCache[$productName])) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(p.id)) FROM product p JOIN product_translation pt ON p.id = pt.product_id WHERE pt.name = :productName COLLATE utf8mb4_bin
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('productName', $productName, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->productIdCache[$productName] = $result;
            }
        }

        return $this->isCacheEnabled() ? $this->productIdCache[$productName] : (string) $result;
    }

    public function selectProductMediaId(string $productId, string $mediaId): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) FROM product_media WHERE product_id = UNHEX(:productId) AND media_id = UNHEX(:mediaId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('mediaId', $mediaId, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function selectProductReviewId(string $productId, string $externalUser, string $externalEmail): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id))
            FROM product_review
            WHERE product_id = UNHEX(:productId)
            AND external_user = :externalUser
            AND external_email = :externalEmail
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('externalUser', $externalUser, \PDO::PARAM_STR);
        $preparedStatement->bindValue('externalEmail', $externalEmail, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function selectProductConfiguratorSettingId(string $productId, string $optionId): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) FROM product_configurator_setting WHERE product_id = UNHEX(:productId) AND property_group_option_id = UNHEX(:optionId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('optionId', $optionId, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function selectProductVisibilityId(string $productId, string $salesChannelId): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) FROM product_visibility WHERE product_id = UNHEX(:productId) AND sales_channel_id = UNHEX(:salesChannelId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function selectProductPriceId(string $productId, string $ruleId, int $quantityStart = 1): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) FROM product_price WHERE product_id = UNHEX(:productId) AND rule_id = UNHEX(:ruleId) AND quantity_start = :quantityStart
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('ruleId', $ruleId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('quantityStart', $quantityStart, \PDO::PARAM_INT);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function selectTaxId(float $taxValue): ?string
    {
        $result = '';
        if (!isset($this->taxIdCache[$taxValue])) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(id)) FROM tax WHERE tax_rate = :taxValue
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('taxValue', $taxValue);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->taxIdCache[$taxValue] = strtolower($result);
            }
        }

        return $this->isCacheEnabled() ? $this->taxIdCache[$taxValue] : strtolower($result);
    }

    public function selectCurrencyId(string $iso): string
    {
        $result = '';
        if (!isset($this->currencyCache[$iso])) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(id)) FROM currency WHERE iso_code = :iso
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('iso', $iso, \PDO::PARAM_STR);
            $result = $preparedStatement->executeQuery()->fetchOne();

            if (null === $result || false === $result) {
                throw new \Exception(sprintf('Currency %s not found', $iso));
            }

            if ($this->isCacheEnabled()) {
                $this->currencyCache[$iso] = strtolower($result);
            }
        }

        return $this->isCacheEnabled() ? $this->currencyCache[$iso] : strtolower($result);
    }

    public function getKeywordConfigFields(string $languageId): array
    {
        if (isset($this->configCache[$languageId])) {
            return $this->configCache[$languageId];
        }

        $query = $this->connection->createQueryBuilder();
        $query->select('configField.field', 'configField.tokenize', 'configField.ranking', 'LOWER(HEX(config.language_id)) as language_id');
        $query->from('product_search_config', 'config');
        $query->join('config', 'product_search_config_field', 'configField', 'config.id = configField.product_search_config_id');
        $query->andWhere('config.language_id IN (:languageIds)');
        $query->andWhere('configField.searchable = 1');

        $query->setParameter('languageIds', Uuid::fromHexToBytesList([$languageId, Defaults::LANGUAGE_SYSTEM]), Connection::PARAM_STR_ARRAY);

        $all = $query->execute()->fetchAllAssociative();

        $fields = array_filter($all, fn (array $field) => $field['language_id'] === $languageId);

        if (!empty($fields)) {
            return $this->configCache[$languageId] = $fields;
        }

        $fields = array_filter($all, fn (array $field) => Defaults::LANGUAGE_SYSTEM === $field['language_id']);

        return $this->configCache[$languageId] = $fields;
    }

    public function storeProduct(
        string $id,
        string $productNumber,
        bool $active,
        int $stock,
        ?string $manufacturerId = null,
        ?string $taxId = null,
        ?PriceCollection $priceCollection = null,
        ?string $parentId = null,
        ?string $ean = null,
        ?array $optionIds = null,
        ?array $propertyIds = null,
        ?array $categoryTree = null,
        ?string $manufacturerNumber = null,
        ?string $productMediaId = null,
        ?bool $available = true,
        ?bool $isCloseOut = false,
        ?bool $shippingFree = false,
        ?bool $markAsTopseller = false,
        ?float $weight = null,
        ?float $width = null,
        ?float $height = null,
        ?float $length = null,
        ?string $deliveryTimeId = null,
        ?string $unitId = null,
        ?int $purchaseSteps = null,
        ?int $minPurchase = null,
        ?int $maxPurchase = null,
        ?float $purchaseUnit = null,
        ?float $referenceUnit = null,
        ?int $availableStock = null,
        ?int $restockTime = null,
        ?\DateTimeInterface $dateTime = null,
        ?float $ratingAverage = null,
        ?string $displayGroup = null,
        ?int $childCount = null,
        ?array $tagIds = null,
        ?array $variantRestrictions = null
    ): void
    {
        $foreignKeyLink = $parentId ?? $id;
        $statement = <<<'SQL'
            INSERT INTO product (id, version_id, product_number, active, parent_id, parent_version_id, tax_id, product_manufacturer_id, product_manufacturer_version_id, delivery_time_id, deliveryTime, product_media_id, product_media_version_id, unit_id, category_tree, option_ids, property_ids, tax, manufacturer, cover, unit, media, prices, visibilities, properties, categories, translations, price, manufacturer_number, ean, stock, available_stock, available, restock_time, is_closeout, purchase_steps, max_purchase, min_purchase, purchase_unit, reference_unit, shipping_free, mark_as_topseller, weight, width, height, length, release_date,tag_ids, tags, variant_restrictions, created_at, rating_average, display_group, child_count, crossSellings)
            VALUES (UNHEX(:id), UNHEX(:versionId), :productNumber, :active, UNHEX(:parentId), UNHEX(:versionId), UNHEX(:taxId), UNHEX(:productManufacturerId), UNHEX(:versionId), UNHEX(:deliveryTimeId), UNHEX(:foreignKeyLink), UNHEX(:productMediaId), UNHEX(:productMediaVersionId), UNHEX(:unitId), :categoryTree, :optionIds, :propertyIds, UNHEX(:taxId), UNHEX(:productManufacturerId), UNHEX(:productMediaId), UNHEX(:unitId), UNHEX(:foreignKeyLink), UNHEX(:foreignKeyLink), UNHEX(:foreignKeyLink), UNHEX(:foreignKeyLink), UNHEX(:foreignKeyLink), UNHEX(:foreignKeyLink), :price, :manufacturerNumber, :ean, :stock, :availableStock, :available, :restockTime, :isCloseOut, :purchaseSteps, :maxPurchase, :minPurchase, :purchaseUnit, :referenceUnit, :shippingFree, :markAsTopseller, :weight, :width, :height, :length, :releaseDate, :tagIds, UNHEX(:id), :variantRestrictions, NOW(), :ratingAverage, :displayGroup, :childCount, UNHEX(:crossSellingId))
            ON DUPLICATE KEY UPDATE active = :active, tax_id = UNHEX(:taxId), parent_id = UNHEX(:parentId), product_manufacturer_id = UNHEX(:productManufacturerId), delivery_time_id = UNHEX(:deliveryTimeId), deliveryTime = UNHEX(:foreignKeyLink), product_media_id = UNHEX(:productMediaId), product_media_version_id = UNHEX(:productMediaVersionId), unit_id = UNHEX(:unitId), category_tree = :categoryTree, option_ids = :optionIds, property_ids = :propertyIds, tax = UNHEX(:taxId), manufacturer = UNHEX(:productManufacturerId), cover = UNHEX(:productMediaId), unit = UNHEX(:unitId), price = :price, manufacturer_number = :manufacturerNumber, ean = :ean, stock = :stock, available_stock = :stock, available = :available, restock_time = :restockTime, is_closeout = :isCloseOut, purchase_steps = :purchaseSteps, max_purchase = :maxPurchase, min_purchase = :minPurchase, purchase_unit = :purchaseUnit, reference_unit = :referenceUnit, shipping_free = :shippingFree, mark_as_topseller = :markAsTopseller, weight = :weight, width = :width, height = :height, length = :length, release_date = :releaseDate, tag_ids = :tagIds, variant_restrictions = :variantRestrictions, updated_at = NOW(), rating_average = :ratingAverage, display_group = :displayGroup, child_count = :childCount
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('foreignKeyLink', $foreignKeyLink, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('productNumber', $productNumber, \PDO::PARAM_STR);
        $preparedStatement->bindValue('active', $active, \PDO::PARAM_INT);
        $preparedStatement->bindValue('parentId', $parentId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('taxId', $taxId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productManufacturerId', $manufacturerId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('deliveryTimeId', $deliveryTimeId);
        $preparedStatement->bindValue('productMediaId', $productMediaId);
        $preparedStatement->bindValue('productMediaVersionId', $productMediaId ? $this->getVersionId() : null);
        $preparedStatement->bindValue('unitId', $unitId);
        $preparedStatement->bindValue('categoryTree', null === $categoryTree ? null : json_encode($categoryTree, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('optionIds', null === $optionIds ? null : json_encode($optionIds, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('propertyIds', null === $propertyIds ? null : json_encode($propertyIds, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('price', null === $priceCollection ? null : json_encode($this->convertPriceCollection($priceCollection), \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('manufacturerNumber', $manufacturerNumber);
        $preparedStatement->bindValue('ean', $ean);
        $preparedStatement->bindValue('stock', $stock, \PDO::PARAM_INT);
        $preparedStatement->bindValue('availableStock', $availableStock ?? $stock, \PDO::PARAM_INT);
        $preparedStatement->bindValue('available', $available);
        $preparedStatement->bindValue('restockTime', $restockTime);
        $preparedStatement->bindValue('isCloseOut', $isCloseOut, \PDO::PARAM_INT);
        $preparedStatement->bindValue('purchaseSteps', $purchaseSteps);
        $preparedStatement->bindValue('minPurchase', $minPurchase);
        $preparedStatement->bindValue('maxPurchase', $maxPurchase);
        $preparedStatement->bindValue('purchaseUnit', $purchaseUnit);
        $preparedStatement->bindValue('referenceUnit', $referenceUnit);
        $preparedStatement->bindValue('shippingFree', $shippingFree, \PDO::PARAM_INT);
        $preparedStatement->bindValue('markAsTopseller', $markAsTopseller, \PDO::PARAM_INT);
        $preparedStatement->bindValue('weight', $weight);
        $preparedStatement->bindValue('width', $width);
        $preparedStatement->bindValue('height', $height);
        $preparedStatement->bindValue('length', $length);
        $preparedStatement->bindValue('releaseDate', null !== $dateTime ? $dateTime->format('Y-m-d H:i:s') : null);
        $preparedStatement->bindValue('tagIds', null === $tagIds ? null : json_encode($tagIds, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('variantRestrictions', null === $variantRestrictions ? null : json_encode($variantRestrictions, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('ratingAverage', $ratingAverage);
        $preparedStatement->bindValue('displayGroup', $displayGroup);
        $preparedStatement->bindValue('childCount', $childCount);
        $preparedStatement->bindValue('crossSellingId', $parentId ?? $id);

        if ($this->isCacheEnabled()) {
            $this->productIdCache[$productNumber] = $id;
        }

        $preparedStatement->executeStatement();
    }

    public function storeProductTranslation(
        string $productId,
        string $languageId,
        ?string $name = null,
        ?string $description = null,
        ?array $customFields = null,
        ?string $keywords = null,
        ?string $metaTitle = null,
        ?string $metaDescription = null,
        ?string $packUnit = null,
        ?array $searchKeywords = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO product_translation (product_id, product_version_id, language_id, meta_description, name, keywords, description, meta_title, pack_unit, custom_fields, created_at, custom_search_keywords)
            VALUES (UNHEX(:productId), UNHEX(:versionId), UNHEX(:languageId), :metaDescription, :name, :keywords, :description, :metaTitle, :packUnit, :customFields, NOW(), :searchKeywords)
            ON DUPLICATE KEY UPDATE name = :name, description = :description, meta_title = :metaTitle, meta_description = :metaDescription, keywords = :keywords, pack_unit = :packUnit, custom_fields = :customFields, updated_at = NOW(), custom_search_keywords = :searchKeywords
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('description', $description, \PDO::PARAM_STR);
        $preparedStatement->bindValue('keywords', $keywords, \PDO::PARAM_STR);
        $preparedStatement->bindValue('metaTitle', $metaTitle, \PDO::PARAM_STR);
        $preparedStatement->bindValue('metaDescription', $metaDescription, \PDO::PARAM_STR);
        $preparedStatement->bindValue('packUnit', $packUnit, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields ? null : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->bindValue(
            'searchKeywords',
            null === $searchKeywords ? null : json_encode($searchKeywords, \JSON_THROW_ON_ERROR)
        );

        $preparedStatement->executeStatement();
    }

    public function storeProductReview(
        string $id,
        string $productId,
        ?string $customerId,
        string $languageId,
        string $salesChannelId,
        bool $status,
        ?string $title,
        \DateTimeInterface $dateTime,
        ?string $content = null,
        float $points = 0,
        ?string $comment = null,
        ?string $externalUser = null,
        ?string $externalEmail = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO product_review (id, product_id, customer_id, sales_channel_id, language_id, external_user, external_email, title, content, points, status, comment, custom_fields, created_at, product_version_id)
            VALUES (UNHEX(:id), UNHEX(:productId), UNHEX(:customerId), UNHEX(:salesChannelId), UNHEX(:languageId), :externalUser, :externalEmail, :title, :content, :points, :status, :comment, :customFields, :created, UNHEX(:versionId))
            ON DUPLICATE KEY UPDATE title = :title,
                                    content = :content,
                                    points = :points,
                                    status = :status,
                                    comment = :comment,
                                    custom_fields = :customFields,
                                    created_at = :created,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('customerId', $customerId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('title', $title, \PDO::PARAM_STR);
        $preparedStatement->bindValue('content', $content, \PDO::PARAM_STR);
        $preparedStatement->bindValue('comment', $comment, \PDO::PARAM_STR);
        $preparedStatement->bindValue('externalUser', $externalUser, \PDO::PARAM_STR);
        $preparedStatement->bindValue('externalEmail', $externalEmail, \PDO::PARAM_STR);
        $preparedStatement->bindValue('created', $dateTime->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $preparedStatement->bindValue('status', $status, \PDO::PARAM_INT);
        $preparedStatement->bindValue('points', $points);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields ? null : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function storeProductCategories(string $productId, \Countable $countable): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_category (product_id, product_version_id, category_id, category_version_id)
            VALUES %s
            SQL;

        $values = '';
        foreach ($countable as $categoryId) {
            if (!\is_string($categoryId)) {
                $categoryId = $categoryId->getId();
            }

            $values .= sprintf(
                '(UNHEX("%s"),UNHEX( "%s"), UNHEX("%s"), UNHEX("%s")),',
                $productId,
                $this->getVersionId(),
                $categoryId,
                $this->getVersionId()
            );
        }

        if (empty($values)) {
            return;
        }

        $preparedStatement = $this->connection->prepare(sprintf($statement, rtrim($values, ',')));
        $preparedStatement->executeStatement();
    }

    public function storeProductCategoryTree(string $productId, array $categories): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_category_tree (product_id, product_version_id, category_id, category_version_id)
            VALUES %s
            SQL;

        $values = '';
        foreach ($categories as $category) {
            if (!\is_string($category)) {
                $category = $category->getId();
            }

            $values .= sprintf(
                '(UNHEX("%s"),UNHEX( "%s"), UNHEX("%s"), UNHEX("%s")),',
                $productId,
                $this->getVersionId(),
                $category,
                $this->getVersionId()
            );
        }

        if (empty($values)) {
            return;
        }

        $preparedStatement = $this->connection->prepare(sprintf($statement, rtrim($values, ',')));
        $preparedStatement->executeStatement();
    }

    public function storeProductTags(string $productId, TagCollection $tagCollection): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_tag (product_id, product_version_id, tag_id)
            VALUES %s
            SQL;

        $values = '';
        foreach ($tagCollection as $tag) {
            if (null === $tag->getId()) {
                $tagId = $this->getTagId($tag->getName());
                $tag->setId($tagId);
            }

            $values .= sprintf(
                '(UNHEX("%s"), UNHEX("%s"), UNHEX("%s")),',
                $productId,
                $this->getVersionId(),
                $tag->getId()
            );
        }

        if (empty($values)) {
            return;
        }

        $preparedStatement = $this->connection->prepare(sprintf($statement, rtrim($values, ',')));
        $preparedStatement->executeStatement();
    }

    public function storeProductProperties(string $productId, \Countable $countable): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_property (product_id, product_version_id, property_group_option_id)
            VALUES %s
            SQL;

        $values = '';
        foreach ($countable as $propertyId) {
            if (!\is_string($propertyId)) {
                $propertyId = $propertyId->getId();
            }
            $values .= sprintf(
                '(UNHEX("%s"), UNHEX("%s"), UNHEX("%s")),',
                $productId,
                $this->getVersionId(),
                $propertyId
            );
        }

        if (empty($values)) {
            return;
        }

        $preparedStatement = $this->connection->prepare(sprintf($statement, rtrim($values, ',')));
        $preparedStatement->executeStatement();
    }

    public function storeProductOptions(string $productId, \Countable $countable): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_option (product_id, product_version_id, property_group_option_id)
            VALUES %s
            SQL;

        $values = '';
        foreach ($countable as $propertyId) {
            if (!\is_string($propertyId)) {
                $propertyId = $propertyId->getId();
            }
            $values .= sprintf(
                '(UNHEX("%s"), UNHEX("%s"), UNHEX("%s")),',
                $productId,
                $this->getVersionId(),
                $propertyId
            );
        }

        if (empty($values)) {
            return;
        }

        $preparedStatement = $this->connection->prepare(sprintf($statement, rtrim($values, ',')));
        $preparedStatement->executeStatement();
    }

    public function resetProductVisibility(string $productId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_visibility
            WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeProductVisibility(
        string $visibilityId,
        string $productId,
        string $salesChannelId,
        ?int $visibility = ProductVisibilityDefinition::VISIBILITY_ALL
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO product_visibility (id, product_id, product_version_id, sales_channel_id, visibility, created_at)
            VALUES (UNHEX(:id), UNHEX(:productId), UNHEX(:versionId), UNHEX(:salesChannelId), :visibility, NOW())
            ON DUPLICATE KEY UPDATE visibility = :visibility, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $visibilityId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'visibility',
            $visibility ?? ProductVisibilityDefinition::VISIBILITY_ALL,
            \PDO::PARAM_INT
        );
        $preparedStatement->executeStatement();
    }

    public function storeProductPrice(
        string $priceId,
        string $productId,
        string $ruleId,
        PriceCollection $priceCollection,
        int $quantityStart = 1,
        ?int $quantityEnd = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO product_price (id, version_id, product_id, product_version_id, rule_id, price, quantity_start, quantity_end, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:productId), UNHEX(:versionId), UNHEX(:ruleId), :price, :quantityStart, :quantityEnd, :customFields, NOW())
            ON DUPLICATE KEY UPDATE price = :price, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $priceId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('ruleId', $ruleId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('price', json_encode($this->convertPriceCollection($priceCollection), \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('customFields', null !== $customFields ? json_encode($customFields, \JSON_THROW_ON_ERROR) : null);
        $preparedStatement->bindValue('quantityStart', $quantityStart, \PDO::PARAM_INT);
        $preparedStatement->bindValue('quantityEnd', $quantityEnd);
        $preparedStatement->executeStatement();
    }

    public function selectProductPrices(string $productId): array
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) as id FROM product_price WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);

        return $preparedStatement->executeQuery()->fetchAllAssociative();
    }

    public function deleteProductPrice(string $id): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_price WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeProductMedia(
        string $productMediaId,
        string $productId,
        string $mediaId,
        ?int $position = 1,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO product_media (id, version_id, position, product_id, product_version_id, media_id, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), :position, UNHEX(:productId), UNHEX(:versionId), UNHEX(:mediaId), :customFields, NOW())
            ON DUPLICATE KEY UPDATE position = :position, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $productMediaId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('mediaId', $mediaId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('customFields', null === $customFields ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('position', $position, \PDO::PARAM_INT);
        $preparedStatement->executeStatement();
    }

    public function storeTax(string $id, float $taxValue, string $name, ?array $customFields = null): void
    {
        $statement = <<<'SQL'
            INSERT INTO tax (id, tax_rate, name, custom_fields, created_at)
            VALUES (UNHEX(:id), :taxRate, :name, :customFields, NOW())
            ON DUPLICATE KEY UPDATE name = :name, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('taxRate', $taxValue);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
                ? null
                : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );

        if ($this->isCacheEnabled()) {
            $this->taxIdCache[$taxValue] = $id;
        }

        $preparedStatement->executeStatement();
    }

    public function storeProductConfiguration(
        string $productId,
        string $optionId,
        string $configuratorSettingId,
        ?string $mediaId = null,
        ?array $price = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO product_configurator_setting (id, version_id, product_id, product_version_id, property_group_option_id, price, media_id, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:productId), UNHEX(:versionId), UNHEX(:optionId), :price, UNHEX(:mediaId), :customFields, NOW())
            ON DUPLICATE KEY UPDATE price = :price, media_id = UNHEX(:mediaId), custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $configuratorSettingId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('optionId', $optionId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('price', null === $price ? null : json_encode($price, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('customFields', null === $customFields ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('mediaId', $mediaId);
        $preparedStatement->executeStatement();
    }

    public function storeProductSearchKeyword(
        string $productId,
        string $languageId,
        AnalyzedKeyword $analyzedKeyword
    ): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_search_keyword (id, version_id, language_id, product_id, product_version_id, keyword, ranking, created_at)
            VALUES (:id, :versionId, :languageId, :productId, :versionId, :keyword, :ranking, NOW())
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', Uuid::randomHex(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('keyword', $analyzedKeyword->getKeyword(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('ranking', $analyzedKeyword->getRanking());
        $preparedStatement->executeStatement();
    }

    public function updateProductDeltaInformation(
        string $productNumber,
        bool $active,
        ?int $stock = null,
        ?PriceCollection $priceCollection = null
    ): void
    {
        $statement = <<<'SQL'
            UPDATE product SET active = :active, updated_at = NOW() %s WHERE product_number = :productNumber
            SQL;

        $setValues = '';
        if (null !== $stock) {
            $setValues .= ', stock = :stock, available_stock = :stock';
        }
        if (null !== $priceCollection) {
            $setValues .= ', price = :price';
        }

        $preparedStatement = $this->connection->prepare(sprintf($statement, $setValues));
        $preparedStatement->bindValue('productNumber', $productNumber, \PDO::PARAM_STR);
        if (null !== $priceCollection) {
            $preparedStatement->bindValue(
                'price',
                json_encode($this->convertPriceCollection($priceCollection), \JSON_THROW_ON_ERROR)
            );
        }
        $preparedStatement->bindValue('stock', $stock);
        $preparedStatement->bindValue('active', $active, \PDO::PARAM_BOOL);
        $preparedStatement->executeStatement();
    }

    public function updateProductOptions(string $productId, ?array $optionIds): void
    {
        $statement = <<<'SQL'
            UPDATE product SET option_ids = :optionIds WHERE id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('optionIds', null === $optionIds ? null : json_encode($optionIds, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('productId', $productId);
        $preparedStatement->executeQuery();
    }

    public function resetProductConfiguration(string $productId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_configurator_setting
            WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->execute();
    }

    public function resetProductTags(string $productId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_tag WHERE product_id = UNHEX(:productId)
            SQL;
        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetProductCategories(string $productId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_category WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetProductCategoryTree(string $productId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_category_tree WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetProductProperties(string $productId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_property WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetProductOptions(string $productId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_option WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetProductKeywords(string $productId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_search_keyword WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function updateProductCategoryTree(string $productId, array $categoryTree): void
    {
        $statement = <<<'SQL'
            UPDATE product SET category_tree = :categoryTree WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('categoryTree', json_encode($categoryTree, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function updateProductChildCount(string $productId): void
    {
        $statement = <<<'SQL'
            UPDATE product SET child_count = IF(child_count IS NULL, 1, child_count + 1) WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $productId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function updateProductVariantDisplayConfiguration(
        string $productId,
        VariantListingConfig $variantListingConfig
    ): void
    {
        $statement = <<<'SQL'
            UPDATE product
            SET variant_listing_config = :variantListingConfig
            WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'variantListingConfig',
            json_encode($variantListingConfig->jsonSerialize()),
            \PDO::PARAM_STR
        );

        $preparedStatement->executeStatement();
    }

    public function selectProductCrossSellingByName(string $name, ?string $languageId = null): ?array
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(pcs.id)) as id,
                   pcs.type,
                   pcst.name,
                   LOWER(HEX(pcst.language_id)) as language_id,
                   pcs.position,
                   pcs.sort_by,
                   pcs.sort_direction,
                   pcs.active,
                   pcs.`limit`,
                   LOWER(HEX(pcs.product_id)) as product_id,
                   LOWER(HEX(pcs.product_version_id)) as product_version_id,
                   LOWER(HEX(pcs.product_stream_id)) as product_stream_id,
                   pcs.created_at
            FROM product_cross_selling pcs
            JOIN product_cross_selling_translation pcst ON pcs.id = pcst.product_cross_selling_id
            WHERE pcst.name = :name COLLATE utf8mb4_bin
            AND pcst.language_id = UNHEX(:languageId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'languageId',
            $languageId ?? Defaults::LANGUAGE_SYSTEM,
            \PDO::PARAM_STR
        );
        $result = $preparedStatement->executeQuery()->fetch();
        if (false === $result || null === $result) {
            return null;
        }

        return $result;
    }

    public function getProductCrossSellings(string $productId, ?string $languageId = null): array
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(pcs.id)) as id,
                   LOWER(HEX(pcs.product_id)) as product_id,
                   LOWER(HEX(pcs.product_stream_id)) as product_stream_id,
                   pcs.type,
                   pcst.name,
                   pcs.position,
                   pcs.active,
                   pcs.limit,
                   pcs.sort_by,
                   pcs.sort_direction
            FROM product_cross_selling pcs
            JOIN product_cross_selling_translation pcst ON pcs.id = pcst.product_cross_selling_id
            WHERE pcs.product_id = UNHEX(:productId)
            AND pcst.language_id = UNHEX(:languageId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'languageId',
            $languageId ?? $this->getDefaultLanguageId(),
            \PDO::PARAM_STR
        );

        return $preparedStatement->executeQuery()->fetchAllAssociative();
    }

    public function deleteProductCrossSelling(string $id): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_cross_selling WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeProductCrossSelling(
        string $id,
        string $productId,
        ?string $productStreamId = null,
        int $position = 0,
        bool $active = true,
        string $type = ProductCrossSellingDefinition::TYPE_PRODUCT_LIST,
        string $sortBy = ProductCrossSellingDefinition::SORT_BY_NAME,
        string $sortDirection = 'ASC',
        int $limit = 24
    ): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_cross_selling (id, type, position, sort_by, sort_direction, active, `limit`, product_id, product_version_id, product_stream_id, created_at)
            VALUES (UNHEX(:id), :type, :position, :sortBy, :sortDirection, :active, :limit, UNHEX(:productId), UNHEX(:versionId), UNHEX(:productStreamId), NOW())
            ON DUPLICATE KEY UPDATE type = :type,
                                    position = :position,
                                    sort_by = :sortBy,
                                    sort_direction = :sortDirection,
                                    active = :active,
                                    `limit` = :limit,
                                    product_stream_id = UNHEX(:productStreamId),
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('type', $type, \PDO::PARAM_STR);
        $preparedStatement->bindValue('position', $position, \PDO::PARAM_INT);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productStreamId', $productStreamId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('sortBy', $sortBy, \PDO::PARAM_STR);
        $preparedStatement->bindValue('sortDirection', $sortDirection, \PDO::PARAM_STR);
        $preparedStatement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $preparedStatement->bindValue('active', $active);
        $preparedStatement->executeStatement();
    }

    public function storeProductCrossSellingTranslation(
        string $productCrossSellingId,
        string $name,
        ?string $languageId = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_cross_selling_translation (product_cross_selling_id, language_id, name, created_at)
            VALUES (UNHEX(:id), UNHEX(:languageId), :name, NOW())
            ON DUPLICATE KEY UPDATE name = :name,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $productCrossSellingId, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'languageId',
            $languageId ?? $this->getDefaultLanguageId(),
            \PDO::PARAM_STR
        );
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeProductCrossSellingAssignedProduct(
        string $productCrossSellingId,
        string $productId,
        int $position = 0
    ): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO product_cross_selling_assigned_products (id, cross_selling_id, product_id, product_version_id, position, created_at)
            VALUES (UNHEX(:id), UNHEX(:crossSellingId), UNHEX(:productId), UNHEX(:versionId), :position, NOW())
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', Uuid::randomHex(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('crossSellingId', $productCrossSellingId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('position', $position, \PDO::PARAM_INT);
        $preparedStatement->executeStatement();
    }

    public function resetProductCrossSellings(string $productCrossSellingId): void
    {
        $statement = <<<'SQL'
            DELETE FROM product_cross_selling_assigned_products WHERE cross_selling_id = UNHEX(:productCrossSellingId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productCrossSellingId', $productCrossSellingId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function selectProductMainCategories(string $productId): array
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) as id,
                   LOWER(HEX(category_id)) as category_id,
                   LOWER(HEX(product_id)) as product_id,
                   LOWER(HEX(sales_channel_id)) as sales_channel_id
            FROM main_category
            WHERE product_id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);

        return $preparedStatement->executeQuery()->fetchAllAssociative();
    }

    public function storeProductMainCategory(
        string $productId,
        string $salesChannelId,
        string $categoryId
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO main_category (id, product_id, product_version_id, category_id, category_version_id, sales_channel_id, created_at)
            VALUES (UNHEX(:id), UNHEX(:productId), UNHEX(:versionId), UNHEX(:categoryId), UNHEX(:versionId), UNHEX(:salesChannelId), NOW())
            ON DUPLICATE KEY UPDATE category_id = UNHEX(:categoryId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', Uuid::randomHex(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('categoryId', $categoryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function deleteProductMainCategory(string $id): void
    {
        $statement = <<<'SQL'
            DELETE FROM main_category WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function selectProductParentId(string $id): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(parent_id)) FROM product WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (false === $result || null === $result) {
            return null;
        }

        return $result;
    }

    public function selectUnitId(string $name, string $languageId): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(unit_id)) FROM unit_translation WHERE name = :name COLLATE utf8mb4_bin and language_id = UNHEX(:languageId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (false === $result || null === $result) {
            return null;
        }

        return $result;
    }

    public function storeUnit(string $id): void
    {
        $statement = <<<'SQL'
            INSERT INTO unit (id, created_at)
            VALUES (UNHEX(:id), NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeUnitTranslation(
        string $id,
        string $languageId,
        string $shortCode,
        string $name,
        ?array $customFields = []
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO unit_translation (unit_id, language_id, short_code, name, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:languageId), :shortCode, :name, :customFields, NOW())
            ON DUPLICATE KEY UPDATE short_code = :shortCode,
                                    name = :name,
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('shortCode', $shortCode, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields ? null : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }
}
