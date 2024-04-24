<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1626764396 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1626764396;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_store_group` (
                `id` BINARY(16) NOT NULL,
                `version_id` BINARY(16) NOT NULL,
                `active` TINYINT(1) NULL DEFAULT '0',
                `display` TINYINT(1) NULL DEFAULT '0',
                `default` TINYINT(1) NULL DEFAULT '0',
                `position` VARCHAR(255) NULL,
                `field_list` JSON NULL,
                `priority` INT(11) NULL,
                `group_zoom_factor` INT(11) NULL,
                `display_below_map` TINYINT(1) NULL DEFAULT '0',
                `media_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`,`version_id`),
                KEY `fk.acris_store_group.media_id` (`media_id`),
                CONSTRAINT `fk.acris_store_group.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_store_group_translation` (
                `internal_name` VARCHAR(255) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `custom_fields` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                `acris_store_group_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `acris_store_group_version_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`acris_store_group_id`,`language_id`,`acris_store_group_version_id`),
                CONSTRAINT `json.acris_store_group_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
                KEY `fk.acris_store_group_translation.acris_store_group_id` (`acris_store_group_id`,`acris_store_group_version_id`),
                KEY `fk.acris_store_group_translation.language_id` (`language_id`),
                CONSTRAINT `fk.acris_store_group_translation.acris_store_group_id` FOREIGN KEY (`acris_store_group_id`,`acris_store_group_version_id`) REFERENCES `acris_store_group` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_store_group_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE `acris_store_locator`
            ADD COLUMN `store_group_id` BINARY(16) NULL,
            ADD COLUMN `acris_store_group_version_id` BINARY(16) NULL,
            ADD KEY `fk.acris_store_locator.store_group_id` (`store_group_id`,`acris_store_group_version_id`),
            ADD CONSTRAINT `fk.acris_store_locator.store_group_id` FOREIGN KEY (`store_group_id`,`acris_store_group_version_id`) REFERENCES `acris_store_group` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE;
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'media', 'acrisStoreGroups');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
