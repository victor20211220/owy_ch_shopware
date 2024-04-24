<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1583135563RentalProduct extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1_583_135_563;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `rental_product` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `mode` INT(11) NOT NULL,
    `buffer` INT(11) NULL,
    `offset` INT(11) NULL,
    `min_period` INT(11) NULL,
    `max_period` INT(11) NULL,
    `deposit_name` VARCHAR(255) NULL,
    `deposit_product_number` VARCHAR(255) NULL,
    `purchasable` TINYINT(1) NOT NULL,
    `active` TINYINT(1) NOT NULL,
    `price` JSON NOT NULL,
    `blocked_periods` JSON NULL,
    `deposit_price` JSON NULL,
    `listing_prices` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    KEY `fk.rental_product.product` (`product_id`, `product_version_id`),
    CONSTRAINT `fk.rental_product.product` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.rental_product.blocked_periods` CHECK (JSON_VALID(`blocked_periods`)),
    CONSTRAINT `json.rental_product.price` CHECK (JSON_VALID(`price`)),
    CONSTRAINT `json.rental_product.deposit_price` CHECK (JSON_VALID(`deposit_price`)),
    CONSTRAINT `json.rental_product.listing_prices` CHECK (JSON_VALID(`listing_prices`))
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
