<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\Uuid\Uuid;

abstract class BaseModule
{
    protected Connection $connection;
    private ?string $versionId = null;
    private array $tagIdCache = [];
    private array $customFieldCache = [];
    private ?string $defaultLanguageId = null;
    private ?string $defaultCmsPageId = null;
    private ?string $defaultSalesChannelId = null;
    private array $currencyCache = [];
    private array $languageIdCache = [];
    private array $mediaFolderCache = [];
    protected bool $isCmsPageOverrideEnabled = false;
    protected bool $isCacheEnabled = true;
    protected bool $isCustomFieldMergeEnabled = true;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getDefaultCmsPage(): string
    {
        if (null === $this->defaultCmsPageId) {
            $this->defaultCmsPageId = $this->getCmsPageId();
        }

        return $this->defaultCmsPageId;
    }

    public function getDefaultSalesChannel(): string
    {
        if (null === $this->defaultSalesChannelId) {
            $this->defaultSalesChannelId = $this->getDefaultSalesChannelId();
        }

        return $this->defaultSalesChannelId;
    }

    public function getCurrencyIso(string $currencyId): ?string
    {
        if (!isset($this->currencyCache[$currencyId])) {
            $statement = <<<'SQL'
                SELECT iso_code
                FROM currency
                WHERE id = UNHEX(:currencyId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('currencyId', $currencyId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (false === $result) {
                return null;
            }
            $this->currencyCache[$currencyId] = $result;
        }

        return $this->currencyCache[$currencyId];
    }

    public function getMediaFolderId(string $folderName): string
    {
        if (!isset($this->mediaFolderCache[$folderName])) {
            $this->mediaFolderCache[$folderName] = $this->getMediaFolderIdByName($folderName);
        }

        return $this->mediaFolderCache[$folderName];
    }

    public function getTagId(string $name): string
    {
        $tagId = $this->selectTagId($name);
        if (null === $tagId) {
            $tagId = Uuid::randomHex();
            $statement = <<<'SQL'
                INSERT INTO tag (id, name, created_at)
                VALUES (UNHEX(:id), :name, NOW())
                SQL;
            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('id', $tagId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
            $preparedStatement->executeStatement();

            $this->tagIdCache[$name] = $tagId;
        }

        return $tagId;
    }

    private function selectTagId(string $name): ?string
    {
        if (!isset($this->tagIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM tag WHERE name = :name COLLATE utf8mb4_bin
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            $this->tagIdCache[$name] = $result;
        }

        return $this->tagIdCache[$name];
    }

    public function getVersionId(): string
    {
        return Defaults::LIVE_VERSION;
    }

    public function setDefaultLanguageId(string $languageId): void
    {
        $this->defaultLanguageId = $languageId;
    }

    public function getDefaultLanguageId(): string
    {
        if (null === $this->defaultLanguageId) {
            $this->defaultLanguageId = Defaults::LANGUAGE_SYSTEM;
        }

        return $this->defaultLanguageId;
    }

    public function getLanguageIdByName(string $languageName): string
    {
        if (!isset($this->languageIdCache[$languageName])) {
            $result = $this->getLanguageId($languageName);

            $this->languageIdCache[$languageName] = $result;
        }

        return $this->languageIdCache[$languageName];
    }

    public function selectCustomFields(
        string $table,
        string $column,
        string $id,
        ?string $languageId
    ): array
    {
        if (!isset($this->customFieldCache[$table . $id . $languageId])) {
            $statement = <<<'SQL'
                SELECT custom_fields
                FROM `%s`
                WHERE %s = UNHEX(:id)
                %s
                SQL;

            $preparedStatement = $this->connection->prepare(
                sprintf(
                    $statement,
                    $table,
                    $column,
                    null !== $languageId ? ' AND language_id = UNHEX(:languageId)' : ''
                )
            );
            $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
            if (null !== $languageId) {
                $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
            }

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return [];
            }

            $this->customFieldCache[$table . $id . $languageId] = json_decode($result, true, 512, \JSON_THROW_ON_ERROR);
        }

        return $this->customFieldCache[$table . $id . $languageId];
    }

    public function getLanguageIdByIsoCode(string $isoCode): string
    {
        if (!isset($this->languageIdCache[$isoCode])) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(l.id))
                FROM language l
                JOIN locale le ON l.locale_id = le.id
                WHERE le.code = :code
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('code', str_replace('_', '-', $isoCode), \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (false === $result) {
                throw new \Exception(sprintf('Default language isoCode %s is not present', $isoCode));
            }

            $this->languageIdCache[$isoCode] = (string) $result;
        }

        return $this->languageIdCache[$isoCode];
    }

    protected function convertPriceCollection(PriceCollection $priceCollection): array
    {
        $converted = [];
        foreach ($priceCollection as $price) {
            $price = json_decode(
                json_encode($price, \JSON_THROW_ON_ERROR),
                true,
                512,
                \JSON_THROW_ON_ERROR
            );
            $converted['c' . $price['currencyId']] = $price;
        }

        return $converted;
    }

    private function getLanguageId(string $languageName): string
    {
        $statement = <<<'SQL'
            SELECT HEX(id)
            FROM language
            WHERE name = :name
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('name', $languageName, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (false === $result) {
            throw new \Exception(sprintf('Default language %s is not present', $languageName));
        }

        return (string) $result;
    }

    private function getMediaFolderIdByName(string $folderName): string
    {
        $statement = <<<'SQL'
            SELECT HEX(id)
            FROM media_folder mf
            JOIN media_default_folder mdf on mf.default_folder_id = mdf.id
            WHERE mf.name = :name
            OR mdf.entity = :name
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('name', $folderName, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (false === $result) {
            throw new \Exception(sprintf('Media folder %s is not present', $folderName));
        }

        return (string) $result;
    }

    private function getDefaultSalesChannelId(): string
    {
        $statement = <<<'SQL'
            SELECT HEX(sales_channel_id)
            FROM sales_channel_translation
            WHERE name != 'Headless'
            LIMIT 1
            SQL;

        $preparedStatement = $this->connection->prepare($statement);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (false === $result) {
            throw new \Exception('Default sales channel is not present');
        }

        return (string) $result;
    }

    private function getCmsPageId(): string
    {
        $statement = <<<'SQL'
            SELECT HEX(cms_page_id)
            FROM cms_page_translation
            WHERE name IN (:nameGer, :nameEng)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('nameGer', 'Default category layout', \PDO::PARAM_STR);
        $preparedStatement->bindValue('nameEng', 'Standard Kategorie-Layout', \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (false === $result) {
            throw new \Exception(sprintf('Default cms page %s is not present', 'Default category layout'));
        }

        return (string) $result;
    }

    public function selectMediaIdByEntity(string $table, string $id): ?string
    {
        $statement = <<<'SQL'
            SELECT HEX(%s) FROM %s WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare(
            sprintf(
                $statement,
                'product' === $table ? 'cover' : 'media_id',
                $table
            )
        );
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return $result;
    }

    protected function isCacheEnabled(): bool
    {
        return $this->isCacheEnabled;
    }

    public function setIsCacheEnabled(bool $isCacheEnabled): self
    {
        $this->isCacheEnabled = $isCacheEnabled;

        return $this;
    }

    public function isCustomFieldMergeEnabled(): bool
    {
        return $this->isCustomFieldMergeEnabled;
    }

    public function setIsCustomFieldMergeEnabled(bool $isCustomFieldMergeEnabled): self
    {
        $this->isCustomFieldMergeEnabled = $isCustomFieldMergeEnabled;

        return $this;
    }

    public function isCmsPageOverrideEnabled(): bool
    {
        return $this->isCmsPageOverrideEnabled;
    }

    public function setIsCmsPageOverrideEnabled(bool $isCmsPageOverrideEnabled): self
    {
        $this->isCmsPageOverrideEnabled = $isCmsPageOverrideEnabled;

        return $this;
    }

    public function disableForeignKeys(): void
    {
        $statement = <<<'SQL'
            SET FOREIGN_KEY_CHECKS = 0;
            SQL;
        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->executeStatement();
    }

    public function enableForeignKeys(): void
    {
        $statement = <<<'SQL'
            SET FOREIGN_KEY_CHECKS = 1;
            SQL;
        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->executeStatement();
    }

    public function startTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commitTransaction(): void
    {
        $this->connection->commit();
    }

    public function rollbackTransaction(): void
    {
        $this->connection->rollBack();
    }
}
