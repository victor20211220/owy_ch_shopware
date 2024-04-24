<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1612966857SearchSynonym extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1612966857;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `s_plugin_netzp_search_log` (
    `id` binary(16) NOT NULL,

    `query` varchar(255) NOT NULL,
    `hits` int unsigned,
    `origin` int unsigned,
    `sales_channel_id` binary(16) NOT NULL,
    `language_id` binary(16) NOT NULL,

    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,

    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE INDEX `idx.origin` ON s_plugin_netzp_search_log(origin);
CREATE INDEX `idx.sales_channel` ON s_plugin_netzp_search_log(sales_channel_id);
CREATE INDEX `idx.language` ON s_plugin_netzp_search_log(language_id);
SQL;

        try {
            $connection->executeStatement($sql);
        }
        catch(\Exception) {
            //
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
