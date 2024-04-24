<?php declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1678792816FixedPeriod extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_678_792_816;
    }

    public function update(Connection $connection): void
    {
        $connection->executeUpdate('ALTER TABLE `rental_product` ADD COLUMN `fixed_period` TINYINT(1) NOT NULL');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
