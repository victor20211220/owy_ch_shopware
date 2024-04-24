<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1649759616 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1649759616;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE acris_order_delivery_store
            ADD COLUMN `version_id` BINARY(16) NOT NULL;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE acris_order_delivery_store
            DROP PRIMARY KEY;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE acris_order_delivery_store
            ADD PRIMARY KEY (`id`, `version_id`);
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            UPDATE `acris_order_delivery_store`
                INNER JOIN `order_delivery`
                ON `order_delivery`.`id` = `acris_order_delivery_store`.`order_delivery_id`
            SET `acris_order_delivery_store`.`version_id` = `order_delivery`.`version_id`
            WHERE `acris_order_delivery_store`.`version_id` = 0x00000000000000000000000000000000;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
