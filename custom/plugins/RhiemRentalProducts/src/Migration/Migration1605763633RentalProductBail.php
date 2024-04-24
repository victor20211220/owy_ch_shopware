<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1605763633RentalProductBail extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_605_763_633;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rental_product` ADD `bail` JSON NULL;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
