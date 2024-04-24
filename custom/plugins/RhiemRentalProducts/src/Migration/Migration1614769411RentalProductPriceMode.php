<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1614769411RentalProductPriceMode extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_614_769_411;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rental_product_price` ADD `mode` INT(11) NOT NULL;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
