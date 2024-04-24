<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

final class PropertyModule extends BaseModule
{
    private array $propertyGroupIdCache = [];
    private array $propertyOptionIdCache = [];

    public function selectPropertyGroupId(string $propertyGroupName, ?string $languageId = null): ?string
    {
        if (null === $languageId) {
            $languageId = $this->getDefaultLanguageId();
        }

        $result = '';
        if (!isset($this->propertyGroupIdCache[$languageId . $propertyGroupName])) {
            $statement = <<<'SQL'
                SELECT HEX(pg.id)
                FROM property_group pg
                JOIN property_group_translation pgt on pg.id = pgt.property_group_id
                WHERE pgt.name = :name COLLATE utf8mb4_bin AND pgt.language_id = UNHEX(:languageId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $propertyGroupName, \PDO::PARAM_STR);
            $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->propertyGroupIdCache[$languageId . $propertyGroupName] = $result;
            }
        }

        return $this->isCacheEnabled() ? $this->propertyGroupIdCache[$languageId . $propertyGroupName] : (string) $result;
    }

    public function storePropertyGroup(
        string $id,
        string $sortingType = 'alphanumeric',
        string $displayType = 'text'
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO property_group (id, sorting_type, display_type, created_at)
            VALUES (UNHEX(:id), :sortingType, :displayType, NOW())
            ON DUPLICATE KEY UPDATE sorting_type = :sortingType, display_type = :displayType, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('sortingType', $sortingType, \PDO::PARAM_STR);
        $preparedStatement->bindValue('displayType', $displayType, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storePropertyGroupTranslation(
        string $propertyGroupId,
        string $languageId,
        ?string $name = null,
        ?string $description = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO property_group_translation (property_group_id, language_id, name, description, custom_fields, created_at)
            VALUES (UNHEX(:propertyGroupId), UNHEX(:languageId), :name, :description, :customFields, NOW())
            ON DUPLICATE KEY UPDATE name = :name, description = :description, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('propertyGroupId', $propertyGroupId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('description', $description);
        $preparedStatement->bindValue('customFields', empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));

        if ($this->isCacheEnabled()) {
            $this->propertyGroupIdCache[$languageId . $name] = $propertyGroupId;
        }

        $preparedStatement->executeStatement();
    }

    public function selectPropertyOptionId(string $propertyGroupId, string $optionName, string $languageId): ?string
    {
        $result = '';
        if (!isset($this->propertyOptionIdCache[$propertyGroupId . $languageId . $optionName])) {
            $statement = <<<'SQL'
                SELECT HEX(pgo.id)
                FROM property_group_option pgo
                JOIN property_group_option_translation pgot on pgo.id = pgot.property_group_option_id
                WHERE pgot.name = :name COLLATE utf8mb4_bin AND pgot.language_id = UNHEX(:languageId) AND pgo.property_group_id = UNHEX(:propertyGroupId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $optionName, \PDO::PARAM_STR);
            $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('propertyGroupId', $propertyGroupId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->propertyOptionIdCache[$propertyGroupId . $languageId . $optionName] = $result;
            }
        }

        return $this->isCacheEnabled() ? $this->propertyOptionIdCache[$propertyGroupId . $languageId . $optionName] : (string) $result;
    }

    public function storePropertyOption(
        string $id,
        string $propertyGroupId,
        ?string $mediaId = null,
        ?string $colorHexCode = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO property_group_option (id, property_group_id, color_hex_code, media_id, created_at)
            VALUES (UNHEX(:id), UNHEX(:propertyGroupId), :colorHexCode, UNHEX(:mediaId), NOW())
            ON DUPLICATE KEY UPDATE color_hex_code = :colorHexCode, media_id = UNHEX(:mediaId), updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('propertyGroupId', $propertyGroupId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('mediaId', $mediaId);
        $preparedStatement->bindValue('colorHexCode', $colorHexCode);

        $preparedStatement->executeStatement();
    }

    public function storePropertyOptionTranslation(
        string $propertyOptionId,
        string $propertyGroupId,
        string $languageId,
        ?string $name = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO property_group_option_translation (property_group_option_id, language_id, name, custom_fields, created_at)
            VALUES (UNHEX(:optionId), UNHEX(:languageId), :name, :customFields, NOW())
            ON DUPLICATE KEY UPDATE name = :name, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('optionId', $propertyOptionId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('customFields', empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));

        if ($this->isCacheEnabled()) {
            $this->propertyOptionIdCache[$propertyGroupId . $languageId . $name] = $propertyOptionId;
        }

        $preparedStatement->executeStatement();
    }
}
