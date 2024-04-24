<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Gateway;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;

class ImportExtensionGateway
{
    private Connection $connection;
    private ?array $customFieldCache = null;
    private array $translation = [];
    public const CUSTOM_FIELD_NAME = 'ott_customer_discountgroup';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function countDeleteProducts(): int
    {
        $statement = <<<'SQL'
            SELECT count(p.id)
            FROM product p
            JOIN product_translation pt
            ON p.id = pt.product_id
            AND pt.language_id = UNHEX(:defaultLanguage)
            WHERE JSON_UNQUOTE(JSON_EXTRACT(pt.custom_fields, '$.ott_custom_lastupdate')) <= NOW() - INTERVAL 1 DAY
            OR JSON_EXTRACT(pt.custom_fields, '$.ott_custom_lastupdate') IS NULL
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('defaultLanguage', Defaults::LANGUAGE_SYSTEM, \PDO::PARAM_STR);

        return (int) $preparedStatement->executeQuery()->fetchOne();
    }

    public function getCustomFieldIdandSetId(): array
    {
        if (null === $this->customFieldCache) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(id)) as id,
                       LOWER(HEX(set_id)) as set_id
                FROM custom_field
                WHERE name = :name
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', self::CUSTOM_FIELD_NAME, \PDO::PARAM_STR);

            $this->customFieldCache = $preparedStatement->executeQuery()->fetchAssociative();

            if (false === $this->customFieldCache) {
                $this->customFieldCache = [];
            }
        }

        return $this->customFieldCache;
    }

    public function getTranslation(string $productNumber, string $languageId): ?array
    {
        if (!isset($this->translation[$productNumber . $languageId])) {
            $statement = <<<'SQL'
                SELECT name, description
                FROM product p
                JOIN product_translation pt
                    ON p.id = pt.product_id
                       AND pt.language_id = UNHEX(:languageId)
                WHERE p.product_number = :productNumber
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('productNumber', $productNumber, \PDO::PARAM_STR);
            $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchAssociative();

            if (false === $result) {
                $result = null;
            }

            $this->translation[$productNumber . $languageId] = $result;
        }

        return $this->translation[$productNumber . $languageId];
    }

    public function deleteProducts(): void
    {
        $statement = <<<'SQL'
            DELETE p FROM product p
            JOIN product_translation pt
            ON p.id = pt.product_id
            AND pt.language_id = UNHEX(:defaultLanguage)
            WHERE JSON_UNQUOTE(JSON_EXTRACT(pt.custom_fields, '$.ott_custom_lastupdate')) <= NOW() - INTERVAL 1 DAY
            OR JSON_EXTRACT(pt.custom_fields, '$.ott_custom_lastupdate') IS NULL
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('defaultLanguage', Defaults::LANGUAGE_SYSTEM, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function getCoverMediaId(string $productId): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(cover))
            FROM product
            WHERE id = UNHEX(:productId)
            AND cover IS NOT NULL
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $result = $preparedStatement->executeQuery()->fetchOne();

        if (null === $result || false === $result) {
            return null;
        }

        return $result;
    }

    public function getCustomerGroupId(string $customerNumber): ?string
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(customer_group_id))
            FROM customer
            WHERE customer_number = :customerNumber
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('customerNumber', $customerNumber, \PDO::PARAM_STR);
        $result = $preparedStatement->executeQuery()->fetchOne();

        if (null === $result || false === $result) {
            return null;
        }

        return $result;
    }

    public function getDefaultShippingAddress(string $customerNumber): ?array
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(cd.id)) as id,
            cd.company,
            cd.department,
            cd.title,
            cd.first_name,
            cd.last_name,
            cd.street,
            cd.zipcode,
            cd.city,
            cd.phone_number,
            cd.additional_address_line1,
            cd.additional_address_line2,
            cd.custom_fields,
            HEX(cd.country_id) as country_id,
            HEX(cd.salutation_id) as salutation_id
            FROM customer_address cd
            JOIN customer c ON cd.id = c.default_shipping_address_id
            WHERE c.customer_number = :customerNumber
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('customerNumber', $customerNumber, \PDO::PARAM_STR);

        return $preparedStatement->executeQuery()->fetchAllAssociative();
    }

    public function getCustomerPassword(string $customerNumber): string
    {
        $statement = <<<'SQL'
            SELECT password
            FROM customer
            WHERE customer_number = :customerNumber
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('customerNumber', $customerNumber, \PDO::PARAM_STR);

        return (string) $preparedStatement->executeQuery()->fetchOne();
    }

    public function selectProductProperties(string $productId, string $languageId): array
    {
        $statement = <<<'SQL'
            SELECT
            pgot.property_group_option_id as id,
            pgt.name as `name`,
            pgot.name as `value`
            FROM product_property pp
            JOIN property_group_option pgo ON pgo.id = pp.property_group_option_id
            JOIN property_group_option_translation pgot ON pgot.property_group_option_id = pp.property_group_option_id
            JOIN property_group_translation pgt ON pgt.property_group_id = pgo.property_group_id
            WHERE pp.product_id = UNHEX(:productId)
            AND pgot.language_id = UNHEX(:languageId)
            AND pgt.language_id = UNHEX(:languageId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);

        return $preparedStatement->executeQuery()->fetchAllAssociative();
    }

    public function selectProductCustomFields(string $productId, string $languageId): array
    {
        $statement = <<<'SQL'
            SELECT custom_fields FROM product_translation WHERE product_id = UNHEX(:productId) AND language_id = UNHEX(:languageId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (false === $result || null === $result || false === json_decode($result, true)) {
            return [];
        }

        return json_decode($result, true);
    }

    public function updateProductCover(string $productId, string $coverId): void
    {
        $statement = <<<'SQL'
            UPDATE product SET product_media_id = UNHEX(:cover), cover = UNHEX(:cover) WHERE id = UNHEX(:productId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('cover', $coverId, \PDO::PARAM_STR);

        $preparedStatement->executeStatement();
    }
}
