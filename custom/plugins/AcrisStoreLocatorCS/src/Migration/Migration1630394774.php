<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1630394774 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1630394774;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE `acris_store_group`
            ADD COLUMN `icon_id` BINARY(16) NULL,
            ADD COLUMN `icon_width` INT(11) NULL,
            ADD COLUMN `icon_height` INT(11) NULL,
            ADD COLUMN `icon_anchor_left` INT(11) NULL,
            ADD COLUMN `icon_anchor_right` INT(11) NULL,
            ADD KEY `fk.acris_store_group.icon_id` (`icon_id`),
            ADD CONSTRAINT `fk.acris_store_group.icon_id` FOREIGN KEY (`icon_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'media', 'acrisStoreGroupIcons');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
