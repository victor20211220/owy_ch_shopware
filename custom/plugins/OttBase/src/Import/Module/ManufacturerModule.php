<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

final class ManufacturerModule extends BaseModule
{
    private array $manufacturerIdCache = [];

    public function storeManufacturer(
        string $manufacturerId,
        ?string $link = null,
        ?string $mediaId = null,
        bool $noUpdate = false
    ): void
    {
        if ($noUpdate) {
            $statement = <<<'SQL'
                INSERT INTO product_manufacturer (id, version_id, link, media_id, created_at)
                VALUES (UNHEX(:id), UNHEX(:versionId), :link, UNHEX(:mediaId), NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
                SQL;
        } else {
            $statement = <<<'SQL'
                INSERT INTO product_manufacturer (id, version_id, link, media_id, created_at)
                VALUES (UNHEX(:id), UNHEX(:versionId), :link, UNHEX(:mediaId), NOW())
                ON DUPLICATE KEY UPDATE link = :link, media_id = UNHEX(:mediaId), updated_at = NOW()
                SQL;
        }

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $manufacturerId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('link', $link);
        $preparedStatement->bindValue('mediaId', $mediaId);
        $preparedStatement->executeStatement();
    }

    public function storeManufacturerTranslation(
        string $manufacturerId,
        string $languageId,
        ?string $name = null,
        ?string $description = null,
        ?array $customFields = null,
        bool $noUpdate = false
    ): void
    {
        if ($noUpdate) {
            $statement = <<<'SQL'
                INSERT INTO product_manufacturer_translation (product_manufacturer_id, product_manufacturer_version_id, language_id, `name`, description, custom_fields, created_at)
                VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:languageId), :name, :description, :customFields, NOW())
                ON DUPLICATE KEY UPDATE name = :name, updated_at = NOW()
                SQL;
        } else {
            $statement = <<<'SQL'
                INSERT INTO product_manufacturer_translation (product_manufacturer_id, product_manufacturer_version_id, language_id, `name`, description, custom_fields, created_at)
                VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:languageId), :name, :description, :customFields, NOW())
                ON DUPLICATE KEY UPDATE name = :name, description = :description, custom_fields = :customFields, updated_at = NOW()
                SQL;
        }

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $manufacturerId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name);
        $preparedStatement->bindValue('description', $description);
        $preparedStatement->bindValue('customFields', empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));

        if ($this->isCacheEnabled()) {
            $this->manufacturerIdCache[$languageId . $name] = $manufacturerId;
        }

        $preparedStatement->executeStatement();
    }

    public function selectManufacturerId(string $name, string $languageId): ?string
    {
        $result = '';
        if (!isset($this->manufacturerIdCache[$languageId . $name])) {
            $statement = <<<'SQL'
                SELECT HEX(id)
                FROM product_manufacturer pm
                JOIN product_manufacturer_translation pmt ON pm.id = pmt.product_manufacturer_id
                WHERE pmt.name = :name COLLATE utf8mb4_bin
                AND pmt.language_id = UNHEX(:language)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
            $preparedStatement->bindValue('language', $languageId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->manufacturerIdCache[$languageId . $name] = $result;
            }
        }

        return $this->isCacheEnabled() ? $this->manufacturerIdCache[$languageId . $name] : (string) $result;
    }
}
