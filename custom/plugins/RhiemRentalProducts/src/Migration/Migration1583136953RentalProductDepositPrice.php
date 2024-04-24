<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1583136953RentalProductDepositPrice extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_583_136_953;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `rental_product_deposit_price` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `rental_product_id` BINARY(16) NOT NULL,
    `rental_product_version_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    `price` JSON NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`,`version_id`),
    CONSTRAINT `json.rental_product_deposit_price.price` CHECK (JSON_VALID(`price`)),
    KEY `fk.rental_product_deposit_price.rental_product_id` (`rental_product_id`,`rental_product_version_id`),
    KEY `fk.rental_product_deposit_price.rule_id` (`rule_id`),
    CONSTRAINT `fk.rental_product_deposit_price.rental_product_id` FOREIGN KEY (`rental_product_id`,`rental_product_version_id`) REFERENCES `rental_product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.rental_product_deposit_price.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
