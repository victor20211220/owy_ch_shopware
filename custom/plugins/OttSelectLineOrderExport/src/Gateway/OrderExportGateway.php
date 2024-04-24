<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Gateway;

use Doctrine\DBAL\Connection;
use Ott\SelectLineOrderExport\Entity\OrderExportEntity;

class OrderExportGateway
{
    private Connection $connection;
    private ?string $germanLanguageId = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOrdersToExport(): array
    {
        $statement = <<<'SQL'
            SELECT HEX(o.id) as id, o.order_number, o.order_date_time, oc.customer_number, oc.email,
                   HEX(o.billing_address_id) as billing_address_id, od.shipping_date_earliest
            FROM `order` o
            JOIN order_customer oc ON o.id = oc.order_id AND o.version_id = oc.order_version_id
            LEFT JOIN order_delivery od ON o.id = od.order_id and o.version_id = od.order_version_id
            LEFT JOIN order_export oe ON o.id = oe.order_id
            WHERE o.order_number IS NOT NULL
            AND oe.exported IS NULL
            GROUP BY o.id
            SQL;

        $preparedStatement = $this->connection->prepare($statement);

        return $preparedStatement->executeQuery()->fetchAllAssociative();
    }

    public function getBillingAddress(string $orderId): ?array
    {
        $statement = <<<'SQL'
            SELECT HEX(oa.id) as id, oa.company, oa.department, oa.title, oa.first_name, oa.last_name, oa.street, oa.zipcode, oa.city,
                   oa.vat_id, oa.phone_number, oa.additional_address_line1, oa.additional_address_line2, c.iso as country_code,
                   st.display_name as salutation
            FROM `order` o
            JOIN order_address oa ON o.billing_address_id = oa.id
                AND o.version_id = oa.order_version_id
            JOIN country c ON oa.country_id = c.id
            JOIN salutation s on oa.salutation_id = s.id
            JOIN salutation_translation st on s.id = st.salutation_id
            WHERE o.id = UNHEX(:orderId)
            AND st.language_id = UNHEX(:languageId)
            ORDER BY o.created_at DESC
            LIMIT 1
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $this->getGermanLanguageId(), \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchAssociative();
        if (null === $result || false === $result) {
            return null;
        }

        return $result;
    }

    public function getShippingAddress(string $orderId): ?array
    {
        $statement = <<<'SQL'
            SELECT HEX(oa.id) as id, oa.company, oa.department, oa.title, oa.first_name, oa.last_name, oa.street, oa.zipcode, oa.city,
                   oa.vat_id, oa.phone_number, oa.additional_address_line1, oa.additional_address_line2, c.iso as country_code,
                   st.display_name as salutation, true as differsFromBilling
            FROM order_delivery od
            JOIN order_address oa ON od.shipping_order_address_id= oa.id
            JOIN country c ON oa.country_id = c.id
            JOIN salutation s on oa.salutation_id = s.id
            JOIN salutation_translation st on s.id = st.salutation_id
            WHERE od.order_id = UNHEX(:orderId)
            AND st.language_id = UNHEX(:languageId)
            ORDER BY od.created_at DESC
            LIMIT 1
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $this->getGermanLanguageId(), \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchAssociative();
        if (null === $result || false === $result) {
            return null;
        }

        return $result;
    }

    public function getOrderDetails(string $orderId): array
    {
        $statement = <<<'SQL'
            SELECT ol.quantity, ol.unit_price, ol.total_price, ol.label, p.product_number, p.weight, JSON_UNQUOTE(JSON_EXTRACT( pt.custom_fields, '$.custom_discount_')) AS voucher
            FROM order_line_item ol
            LEFT JOIN product p on ol.product_id = p.id AND ol.product_version_id = p.version_id
            LEFT JOIN promotion_translation pt ON pt.name = ol.label AND product_id IS NULL
            WHERE ol.order_id = UNHEX(:orderId)
            GROUP BY ol.id
            ORDER BY position
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);

        return $preparedStatement->executeQuery()->fetchAllAssociative();
    }

    public function setOrderExported(OrderExportEntity $entity): void
    {
        $statement = <<<'SQL'
            INSERT INTO order_export (order_id, exported, created_at)  VALUES (UNHEX(:orderId), 1, NOW())
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('orderId', $entity->getOrderId(), \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    private function getGermanLanguageId(): string
    {
        if (null === $this->germanLanguageId) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM language WHERE name = 'DE'
                SQL;
            $preparedStatement = $this->connection->prepare($statement);

            $this->germanLanguageId = (string) $preparedStatement->executeQuery()->fetchOne();
        }

        return $this->germanLanguageId;
    }
}
