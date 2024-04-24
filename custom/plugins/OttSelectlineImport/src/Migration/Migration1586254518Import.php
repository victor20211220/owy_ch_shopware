<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1586254518Import extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1586254518;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `import_message` (
                `id` BINARY(16) NOT NULL,
                `workload` JSON NULL,
                `file` VARCHAR(255) NOT NULL,
                `type` VARCHAR(255) NOT NULL,
                `created_at` datetime(3) NULL,
                `updated_at` datetime(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.import_message.workload` CHECK(JSON_VALID(`workload`))
            )
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8mb4
            COLLATE = utf8mb4_unicode_ci;
            SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
