<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1580206549SearchSynonym extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1580206549;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `s_plugin_netzp_search_synonyms` (
    `id` binary(16) NOT NULL,

    `synonym` varchar(255),
    `replace` varchar(255),

    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,

    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
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
