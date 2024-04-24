<?php declare(strict_types=1);

namespace Acris\StoreLocator\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1629278697 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1629278697;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE `acris_store_locator`
            ADD COLUMN `cms_page_id` BINARY(16) NULL,
            ADD COLUMN `cms_page_version_id` BINARY(16) NULL,
            ADD KEY `fk.acris_store_locator.cms_page_id` (`cms_page_id`,`cms_page_version_id`),
            ADD CONSTRAINT `fk.acris_store_locator.cms_page_id` FOREIGN KEY (`cms_page_id`,`cms_page_version_id`) REFERENCES `cms_page` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE `acris_store_locator_translation`
            ADD COLUMN `seo_url` VARCHAR(255) NULL,
            ADD COLUMN `meta_title` VARCHAR(255) NULL,
            ADD COLUMN `meta_description` VARCHAR(255) NULL,
            ADD COLUMN `slot_config` JSON NULL;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE `acris_store_group_translation`
            ADD COLUMN `seo_url` VARCHAR(255) NULL,
            ADD COLUMN `meta_title` VARCHAR(255) NULL,
            ADD COLUMN `meta_description` VARCHAR(255) NULL;
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'cms_page', 'acrisStoreLocator');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
