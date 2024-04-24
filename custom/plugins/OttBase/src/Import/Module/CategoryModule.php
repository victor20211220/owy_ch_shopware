<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tag\TagCollection;

final class CategoryModule extends BaseModule
{
    private array $categoryPathCache = [];
    private array $categoryIdCache = [];
    private array $categoryNameCache = [];
    private array $parentPathCache = [];

    public function getCategoryIdByPath(
        string $path,
        string $languageId,
        ?string $parentId = null,
        ?string $cmsPageId = null,
        ?TagCollection $tagCollection = null,
        string $pathDelimiter = '|',
        bool $active = true,
        bool $visible = true,
        int $childCount = 0,
        ?string $mediaId = null,
        string $type = 'page',
        bool $displayNestedProducts = true,
        ?string $afterCategoryId = null,
        ?array $customFields = null,
        ?string $metaTitle = null,
        ?string $metaDescription = null,
        ?string $metaKeywords = null,
        ?string $cmsText = null,
        ?array $translations = [],
        bool $disabledMediaUploads = false,
        ?string $externalLink = null,
        ?array $slotConfig = null
    ): ?string
    {
        if (empty($path)) {
            return null;
        }

        $cacheIdent = null === $cmsPageId ? $path : $path . $cmsPageId;
        if (!isset($this->categoryPathCache[$cacheIdent])) {
            $categoryPath = null === $parentId ? null : $this->getStaticParentPath($parentId);
            $categoryNames = explode($pathDelimiter, $path);
            foreach ($categoryNames as $key => $categoryName) {
                if (empty($categoryName)) {
                    break;
                }

                $categoryId = $this->selectCategoryId($categoryName, $languageId, $parentId);

                if ($disabledMediaUploads && $categoryId) {
                    $mediaId = $this->selectMediaIdByEntity(
                        CategoryDefinition::ENTITY_NAME,
                        $categoryId
                    );
                }

                // custom fields are only assigned to target category / last category in path
                $persistingCustomFields = null;
                if ($key === array_key_last($categoryNames)) {
                    $persistingCustomFields = $customFields;
                }

                // we cant update her cause parent category may already been imported with other cms and media id and customFields
                if (
                    null === $categoryId
                    || $key === array_key_last($categoryNames)
                ) {
                    $isNewCategory = false;
                    if (null === $categoryId) {
                        $isNewCategory = true;
                        $categoryId = Uuid::randomHex();
                    } elseif (!$this->isCmsPageOverrideEnabled()) {
                        $existingCmsPageId = $this->selectCategoryCmsPageId($categoryId);
                        if (null !== $existingCmsPageId) {
                            $cmsPageId = $existingCmsPageId;
                        }
                    }

                    $categoryPath = null === $categoryPath ? null : $categoryPath . '|';
                    $this->storeCategory(
                        $categoryId,
                        $this->getCategoryLevel($categoryPath),
                        $categoryPath ? strtolower(str_replace('||', '|', $categoryPath)) : null,
                        $parentId,
                        $cmsPageId,
                        $active,
                        $visible,
                        $childCount,
                        $mediaId,
                        $type,
                        $displayNestedProducts,
                        $afterCategoryId
                    );

                    if (!$isNewCategory && $this->isCustomFieldMergeEnabled()) {
                        $persistingCustomFields = array_merge(
                            $persistingCustomFields ?? [],
                            $this->selectCustomFields(
                                CategoryTranslationDefinition::ENTITY_NAME,
                                'category_id',
                                $categoryId,
                                $languageId
                            )
                        );
                    }

                    $this->storeCategoryTranslation(
                        $categoryId,
                        $languageId,
                        $categoryName,
                        $cmsText,
                        null,
                        $metaTitle,
                        $metaDescription,
                        $metaKeywords,
                        $persistingCustomFields,
                        $externalLink,
                        $slotConfig
                    );

                    foreach ($translations as $translationLanguageId => $translation) {
                        if (!$isNewCategory && $this->isCustomFieldMergeEnabled()) {
                            $translation['customFields'] = array_merge(
                                $translation['customFields'] ?? [],
                                $this->selectCustomFields(
                                    CategoryTranslationDefinition::ENTITY_NAME,
                                    'category_id',
                                    $categoryId,
                                    $translationLanguageId
                                )
                            );
                        }

                        $this->storeCategoryTranslation(
                            $categoryId,
                            $translationLanguageId,
                            $translation['name'] ?? null,
                            $translation['cmsText'] ?? null,
                            null,
                            $translation['metaTitle'] ?? null,
                            $translation['metaDescription'] ?? null,
                            $translation['metaKeywords'] ?? null,
                            empty($translation['customFields']) ? null : $translation['customFields'],
                            empty($translation['externalLink']) ? null : $translation['externalLink'],
                            empty($translation['slotConfig']) ? null : $translation['slotConfig']
                        );
                    }
                }

                if (null !== $parentId) {
                    $this->updateCategoryChildCount($parentId);
                }

                if ($this->isCacheEnabled()) {
                    $cacheIdent = $this->getCacheIdent($categoryName, $languageId, $parentId);
                    $this->categoryIdCache[$cacheIdent] = $categoryId;
                }

                $categoryPath .= '|' . $categoryId;
                $parentId = $categoryId;
            }

            if ($this->isCacheEnabled()) {
                $this->categoryPathCache[$cacheIdent] = $parentId;
            }
        }

        if (!empty($tagCollection)) {
            $this->storeCategoryTags($this->categoryPathCache[$cacheIdent], $tagCollection);
        }

        return $this->isCacheEnabled() ? $this->categoryPathCache[$cacheIdent] : $parentId;
    }

    public function selectCategoryId(string $name, string $languageId, ?string $parentId = null): ?string
    {
        $cacheIdent = $this->getCacheIdent($name, $languageId, $parentId);
        $result = '';
        if (!isset($this->categoryIdCache[$cacheIdent])) {
            $statement = <<<'SQL'
                SELECT HEX(c.id)
                FROM category c
                JOIN category_translation ct ON c.id = ct.category_id
                WHERE ct.name = :name COLLATE utf8mb4_bin
                AND c.parent_id %s
                AND ct.language_id = UNHEX(:languageId)
                SQL;

            $preparedStatement = $this->connection->prepare(
                sprintf(
                    $statement,
                    null === $parentId ? 'IS NULL' : '= UNHEX(\'' . $parentId . '\')'
                )
            );
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
            $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->categoryIdCache[$cacheIdent] = $result;
            }
        }

        return $this->isCacheEnabled() ? $this->categoryIdCache[$cacheIdent] : (string) $result;
    }

    public function selectCategoryCmsPageId(string $categoryId): ?string
    {
        $statement = <<<'SQL'
            SELECT HEX(cms_page_id) as cms_page_id FROM category WHERE id = UNHEX(:categoryId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('categoryId', $categoryId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (false === $result) {
            return null;
        }

        return $result;
    }

    public function storeCategoryTranslation(
        string $categoryId,
        string $languageId,
        ?string $name = null,
        ?string $description = null,
        ?array $breadcrumb = null,
        ?string $metaTitle = null,
        ?string $metaDescription = null,
        ?string $keywords = null,
        ?array $customFields = null,
        ?string $externalLink = null,
        ?array $slotConfig = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO category_translation (category_id, category_version_id, language_id, `name`, breadcrumb, external_link, link_type, description, meta_title, meta_description, keywords, custom_fields, slot_config, created_at)
            VALUES (UNHEX(:categoryId), UNHEX(:versionId), UNHEX(:languageId), :name, :breadcrumb, :externalLink, :linkType, :description, :metaTitle, :metaDescription, :keywords, :customFields, :slotConfig, NOW())
            ON DUPLICATE KEY UPDATE name = :name, breadcrumb = :breadcrumb, external_link = :externalLink, link_type = :linkType, description = :description, meta_title = :metaTitle, meta_description = :metaDescription, keywords = :keywords, custom_fields = :customFields, slot_config = :slotConfig, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('categoryId', $categoryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('description', $description);
        $preparedStatement->bindValue('breadcrumb', null === $breadcrumb ? null : json_encode($breadcrumb, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('metaTitle', $metaTitle);
        $preparedStatement->bindValue('metaDescription', $metaDescription);
        $preparedStatement->bindValue('keywords', $keywords);
        $preparedStatement->bindValue('customFields', empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('externalLink', $externalLink);
        $preparedStatement->bindValue('linkType', !empty($externalLink) ? 'external' : null);
        $preparedStatement->bindValue('slotConfig', null === $slotConfig ? null : json_encode($slotConfig, \JSON_THROW_ON_ERROR));
        $preparedStatement->executeStatement();
    }

    public function storeCategory(
        string $id,
        int $level,
        ?string $path,
        ?string $parentId = null,
        ?string $cmsPageId = null,
        bool $active = true,
        bool $visible = true,
        int $childCount = 0,
        ?string $mediaId = null,
        string $type = 'page',
        bool $displayNestedProducts = true,
        ?string $afterCategoryId = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO category (id, version_id, parent_id, parent_version_id, after_category_id, after_category_version_id, media_id, cms_page_id, cms_page_version_id, `path`, `level`, `active`, child_count, display_nested_products, `visible`, type, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:parentId), UNHEX(:parentVersionId), UNHEX(:afterCategoryId), UNHEX(:versionId),UNHEX(:mediaId), UNHEX(:cmsPageId), UNHEX(:versionId), :path, :level, :active, :childCount, :displayNestedProducts, :visible, :type, NOW())
            ON DUPLICATE KEY UPDATE media_id = UNHEX(:mediaId), cms_page_id = UNHEX(:cmsPageId), path = :path, level = :level, active = :active, child_count = :childCount, display_nested_products = :displayNestedProducts, visible = :visible, type = :type, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('parentId', $parentId);
        $preparedStatement->bindValue('afterCategoryId', $afterCategoryId);
        $preparedStatement->bindValue('parentVersionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('mediaId', $mediaId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('cmsPageId', $cmsPageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('path', $path, \PDO::PARAM_STR);
        $preparedStatement->bindValue('level', $level, \PDO::PARAM_INT);
        $preparedStatement->bindValue('active', $active, \PDO::PARAM_INT);
        $preparedStatement->bindValue('childCount', $childCount, \PDO::PARAM_INT);
        $preparedStatement->bindValue('displayNestedProducts', $displayNestedProducts, \PDO::PARAM_INT);
        $preparedStatement->bindValue('visible', $visible, \PDO::PARAM_INT);
        $preparedStatement->bindValue('type', $type, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeCategoryTags(string $categoryId, TagCollection $tagCollection): void
    {
        $statement = <<<'SQL'
            INSERT IGNORE INTO category_tag (category_id, category_version_id, tag_id)
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
                $categoryId,
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

    public function getCategoryTree(string $categoryId, bool $isHumanReadable = false, ?string $languageId = null): array
    {
        $statement = <<<'SQL'
            SELECT path FROM category WHERE id = UNHEX(:categoryId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('categoryId', $categoryId, \PDO::PARAM_STR);

        $path = $preparedStatement->executeQuery()->fetchOne();
        if (empty($path)) {
            return [];
        }

        if ($isHumanReadable && null === $languageId) {
            $languageId = $this->getDefaultLanguageId();
        }

        $categoryTree = explode('|', trim($path, '|'));
        foreach ($categoryTree as $key => $category) {
            if (empty($category)) {
                unset($categoryTree[$key]);
            }

            $categoryTree[$key] = $isHumanReadable
                ? $this->getCategoryNameById($category, $languageId)
                : strtolower($category);
        }
        $categoryTree[] = $isHumanReadable
            ? $this->getCategoryNameById($categoryId, $languageId)
            : strtolower($categoryId);

        return $categoryTree;
    }

    public function getProductCategoryTree(string $productId): ?array
    {
        $statement = <<<'SQL'
            SELECT category_tree FROM product WHERE id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);

        $categoryTree = $preparedStatement->executeQuery()->fetchOne();
        if (false === $categoryTree || null === $categoryTree) {
            return null;
        }

        return json_decode($categoryTree, true, 512, \JSON_THROW_ON_ERROR);
    }

    private function getCacheIdent(string $name, string $languageId, ?string $parentId = null): string
    {
        return null === $parentId ? $name . $languageId : $name . $languageId . $parentId;
    }

    private function getCategoryLevel(?string $categoryPath): int
    {
        if (null === $categoryPath) {
            return 1;
        }

        $pathElements = explode('|', $categoryPath);
        $elementCount = \count($pathElements) - 1;

        if (1 > $elementCount) {
            return 1;
        }

        return $elementCount;
    }

    private function getStaticParentPath(string $parentId): string
    {
        $result = '';
        if (!isset($this->parentPathCache[$parentId])) {
            $statement = <<<'SQL'
                SELECT path FROM category WHERE id = :parentId
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('parentId', $parentId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();

            if ($this->isCacheEnabled()) {
                $this->parentPathCache[$parentId] = $result;

                if (null === $result || false === $result) {
                    $this->parentPathCache[$parentId] = '';
                }
            }
        }

        return $this->isCacheEnabled() ? $this->parentPathCache[$parentId] : (string) $result;
    }

    public function resetCategoryTags(string $categoryId): void
    {
        $statement = <<<'SQL'
            DELETE FROM category_tag WHERE category_id = UNHEX(:categoryId)
            SQL;
        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('categoryId', $categoryId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function updateCategoryChildCount(string $categoryId): void
    {
        $statement = <<<'SQL'
            UPDATE category SET child_count = IF(child_count IS NULL, 1, child_count + 1) WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $categoryId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function getCategoryNameById(string $categoryId, string $languageId): ?string
    {
        if (!isset($this->categoryNameCache[$categoryId . $languageId])) {
            $statement = <<<'SQL'
                SELECT name
                FROM category c
                JOIN category_translation ct ON c.id = ct.category_id
                WHERE language_id = UNHEX(:languageId)
                AND category_id = UNHEX(:id)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('id', $categoryId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (empty($result)) {
                return null;
            }

            $this->categoryNameCache[$categoryId . $languageId] = $result;
        }

        return $this->categoryNameCache[$categoryId . $languageId];
    }
}
