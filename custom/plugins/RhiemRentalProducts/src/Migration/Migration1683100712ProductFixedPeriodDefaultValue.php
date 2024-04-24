<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1683100712ProductFixedPeriodDefaultValue extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1683100712;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE rental_product MODIFY COLUMN fixed_period TINYINT(1) NOT NULL DEFAULT 0;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
