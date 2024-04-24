<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1625042734 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1625042734;
    }

    public function update(Connection $connection): void
    {
        $this->updateInheritance($connection, 'order_delivery', 'acrisOrderDeliveryStore');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
