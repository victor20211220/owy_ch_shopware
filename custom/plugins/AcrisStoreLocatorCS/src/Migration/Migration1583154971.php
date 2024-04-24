<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1583154971 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1583154971;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_store_locator` (
                `id` BINARY(16) NOT NULL,
                `country_id` BINARY(16) NULL,
                `city` VARCHAR(255) NOT NULL,
                `zipcode` VARCHAR(255) NOT NULL,
                `street` VARCHAR(255) NOT NULL,
                `active` TINYINT(1) NULL DEFAULT '0',
                `longitude` VARCHAR(255) NULL,
                `latitude` VARCHAR(255) NULL,
                 `handlerpoints` VARCHAR(255) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.acris_store_locator.country_id` (`country_id`),
                CONSTRAINT `fk.acris_store_locator.country_id` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_store_locator_translation` (
                `name` VARCHAR(255) NOT NULL,
                `department` VARCHAR(255) NULL,
                `phone` VARCHAR(255) NULL,
                `email` VARCHAR(255) NULL,
                `url` VARCHAR(255) NULL,
                `opening_hours` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                `acris_store_locator_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`acris_store_locator_id`,`language_id`),
                KEY `fk.acris_store_locator_translation.acris_store_locator_id` (`acris_store_locator_id`),
                KEY `fk.acris_store_locator_translation.language_id` (`language_id`),
                CONSTRAINT `fk.acris_store_locator_translation.acris_store_locator_id` FOREIGN KEY (`acris_store_locator_id`) REFERENCES `acris_store_locator` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_store_locator_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
