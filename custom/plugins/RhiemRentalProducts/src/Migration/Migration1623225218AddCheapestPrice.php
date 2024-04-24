<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1623225218AddCheapestPrice extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_623_225_218;
    }

    public function update(Connection $connection): void
    {
        $connection->executeUpdate('ALTER TABLE `rental_product` ADD `cheapest_price` longtext NULL;');
        $connection->executeUpdate('ALTER TABLE `rental_product` ADD `cheapest_price_accessor` longtext NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `rental_product` DROP `listing_prices`');
    }
}
