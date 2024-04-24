<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1676011545AddRuleTable extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1676011545;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_store_rule` (
                `store_locator_id` BINARY(16) NOT NULL,
                `rule_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                PRIMARY KEY (`store_locator_id`,`rule_id`),
                KEY `fk.acris_store_rule.store_locator_id` (`store_locator_id`),
                KEY `fk.acris_store_rule.rule_id` (`rule_id`),
                CONSTRAINT `fk.acris_store_rule.store_locator_id` FOREIGN KEY (`store_locator_id`) REFERENCES `acris_store_locator` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_store_rule.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'rule', 'acrisStoreLocator');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
