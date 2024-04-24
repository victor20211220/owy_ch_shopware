<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1630912041 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1630912041;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE `acris_store_locator`
            ADD COLUMN `priority` INT(11) NULL;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
