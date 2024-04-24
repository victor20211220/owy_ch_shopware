<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1665578463 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1665578463;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_store_sales_channel` (
                `store_locator_id` BINARY(16) NOT NULL,
                `sales_channel_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                PRIMARY KEY (`store_locator_id`,`sales_channel_id`),
                KEY `fk.acris_store_locator_sales_channel.store_locator_id` (`store_locator_id`),
                KEY `fk.acris_store_locator_sales_channel.sales_channel_id` (`sales_channel_id`),
                CONSTRAINT `fk.acris_store_locator_sales_channel.store_locator_id` FOREIGN KEY (`store_locator_id`) REFERENCES `acris_store_locator` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_store_locator_locator_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'sales_channel', 'acrisStoreLocator');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
