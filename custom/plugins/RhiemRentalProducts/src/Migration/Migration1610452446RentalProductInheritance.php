<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1610452446RentalProductInheritance extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1_610_452_446;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
                ALTER TABLE `rental_product` 
                MODIFY `mode` INT(11) NULL,
                MODIFY `purchasable` TINYINT(1) NULL,
                MODIFY `active` TINYINT(1) NULL,
                MODIFY `price` JSON NULL,
                ADD COLUMN `parent_id` BINARY(16) NULL AFTER `product_version_id`,
                ADD COLUMN `parent_version_id` BINARY(16) NULL AFTER `parent_id` ,
                ADD COLUMN `prices` BINARY(16) NULL AFTER `price`,
                ADD COLUMN `tax_id` BINARY(16) NULL AFTER `prices`,
				ADD COLUMN `tax` BINARY(16) NULL AFTER `tax_id`,
                ADD COLUMN `child_count` INT(11) unsigned NULL DEFAULT '0' AFTER `listing_prices`,
                ADD CONSTRAINT `fk.rental_product.parent_id` FOREIGN KEY (`parent_id`, `parent_version_id`)
                REFERENCES `rental_product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD CONSTRAINT `fk.rental_product.tax_id` FOREIGN KEY (`tax_id`)
                REFERENCES `tax` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
SQL;
        $connection->executeUpdate($sql);

        $sql = <<<SQL
                UPDATE `rental_product` 
                LEFT JOIN product ON product.id = rental_product.product_id
                SET rental_product.tax_id = product.tax_id
                ;
SQL;
        $connection->executeUpdate($sql);

        $sql = <<<SQL
                ALTER TABLE `rental_product` 
                ADD COLUMN `bail_active` TINYINT(1) NULL AFTER `tax_id`,
                ADD COLUMN `bail_tax_id` BINARY(16) NULL AFTER `bail_active`,
                ADD COLUMN `bail_price` JSON NULL AFTER `bail_tax_id`,
                ADD COLUMN `bailtax` BINARY(16) NULL AFTER `bail_price`,
                ADD CONSTRAINT `fk.rental_product.bail_tax_id` FOREIGN KEY (`bail_tax_id`)
                REFERENCES `tax` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ;
SQL;
        $connection->executeUpdate($sql);

        $sql = <<<SQL
                UPDATE `rental_product` 
                SET rental_product.bail_price = JSON_EXTRACT(IFNULL(IF(bail = '',null,bail),'{}'), '$.price'),
                    rental_product.bail_tax_id = UNHEX(JSON_UNQUOTE(JSON_EXTRACT(IFNULL(IF(bail = '',null,bail),'{}'), '$.taxId'))),
                    rental_product.bail_active = CASE WHEN JSON_EXTRACT(IFNULL(IF(bail = '',null,bail),'{}'), '$.active') = 'true' THEN 1 ELSE 0 END				
                ;
SQL;

        $connection->executeUpdate($sql);

        $sql = <<<SQL
                ALTER TABLE `rental_product` 
                DROP `bail`;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
