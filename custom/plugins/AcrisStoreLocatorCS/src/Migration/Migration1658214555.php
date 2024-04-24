<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1658214555 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1658214555;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE `acris_store_locator`
                MODIFY COLUMN `street` VARCHAR(255) NULL,
                MODIFY COLUMN `zipcode` VARCHAR(255) NULL,
                MODIFY COLUMN `city` VARCHAR(255) NULL;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE `acris_store_locator_translation`
                MODIFY COLUMN `name` VARCHAR(255) NULL;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
