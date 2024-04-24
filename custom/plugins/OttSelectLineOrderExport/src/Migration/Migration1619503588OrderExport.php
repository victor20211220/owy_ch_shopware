<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1619503588OrderExport extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1619503588;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `order_export` (
                `order_id` BINARY(16) NOT NULL,
                `exported` tinyint(1) NOT NULL,
                `created_at` datetime(3) NOT NULL,
                PRIMARY KEY (`order_id`)
            )
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8mb4
            COLLATE = utf8mb4_unicode_ci;
            SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
