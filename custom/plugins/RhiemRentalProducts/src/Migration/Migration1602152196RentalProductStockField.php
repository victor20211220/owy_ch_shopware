<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1602152196RentalProductStockField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_602_152_196;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rental_product` ADD `original_stock` SMALLINT NULL;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
