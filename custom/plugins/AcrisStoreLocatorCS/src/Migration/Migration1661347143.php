<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1661347143 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1661347143;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE `acris_store_locator`
                ADD COLUMN `internal_id` VARCHAR(255) NULL;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE `acris_store_group`
                ADD COLUMN `internal_id` VARCHAR(255) NULL;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
