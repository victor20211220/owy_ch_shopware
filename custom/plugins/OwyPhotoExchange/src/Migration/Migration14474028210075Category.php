<?php

declare(strict_types=1);

namespace OwyPhotoExchange\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;


class Migration14474028210075Category extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 14474028210075;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE IF NOT EXISTS `photo_exchange_category` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255),
                `is_active` BOOLEAN DEFAULT true,                   
                `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');


    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
