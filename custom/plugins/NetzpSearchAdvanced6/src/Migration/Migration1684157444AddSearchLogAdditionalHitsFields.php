<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1684157444AddSearchLogAdditionalHitsFields extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1684157444;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `s_plugin_netzp_search_log`
        ADD `additional_hits` JSON NULL,
        ADD CONSTRAINT `json.s_plugin_netzp_search_log.additional_hits`
                 CHECK (JSON_VALID(`additional_hits`));
SQL;
        try {
            $connection->executeStatement($sql);
        }
        catch (\Exception) {
            //
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
