<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1623908689 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1623908689;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_order_delivery_store` (
    `id` BINARY(16) NOT NULL,
    `store_id` BINARY(16) NOT NULL,
    `order_delivery_id` BINARY(16) NOT NULL,
    `order_delivery_version_id` BINARY(16) NOT NULL,
    `country_id` BINARY(16) NULL,
    `name` VARCHAR(255) NOT NULL,
    `department` VARCHAR(255) NULL,
    `city` VARCHAR(255) NOT NULL,
    `zipcode` VARCHAR(255) NOT NULL,
    `street` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `url` VARCHAR(255) NULL,
    `opening_hours` LONGTEXT NULL,
    `longitude` VARCHAR(255) NULL,
    `latitude` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.acris_order_delivery_store.country_id` (`country_id`),
    KEY `fk.acris_order_delivery_store.order_delivery_id` (`order_delivery_id`,`order_delivery_version_id`),
    KEY `fk.acris_order_delivery_store.store_id` (`store_id`),
    CONSTRAINT `fk.acris_order_delivery_store.country_id` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.acris_order_delivery_store.order_delivery_id` FOREIGN KEY (`order_delivery_id`,`order_delivery_version_id`) REFERENCES `order_delivery` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.acris_order_delivery_store.store_id` FOREIGN KEY (`store_id`) REFERENCES `acris_store_locator` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
