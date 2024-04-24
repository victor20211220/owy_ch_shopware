<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1654235984 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1654235984;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_store_media` (
                `id` BINARY(16) NOT NULL,
                `version_id` BINARY(16) NOT NULL,
                `store_id` BINARY(16) NOT NULL,
                `media_id` BINARY(16) NOT NULL,
                `position` INT(11) NULL,
                `custom_fields` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`,`version_id`),
                CONSTRAINT `json.acris_store_media.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
                KEY `fk.acris_store_media.store_id` (`store_id`),
                KEY `fk.acris_store_media.media_id` (`media_id`),
                CONSTRAINT `fk.acris_store_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE acris_store_locator
            ADD COLUMN `state_id` BINARY(16) NULL,
            ADD COLUMN `store_media_id` BINARY(16) NULL,
            ADD COLUMN `acris_store_media_version_id` BINARY(16) NOT NULL,
            ADD KEY `fk.acris_store_locator.store_media_id` (`store_media_id`,`acris_store_media_version_id`);
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'country_state', 'acrisStores');
        $this->updateInheritance($connection, 'media', 'storeMedia');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
