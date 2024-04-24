<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1604053182RentalProductAddJsonField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_604_053_182;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rental_product` ADD `rental_times` JSON NULL;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
