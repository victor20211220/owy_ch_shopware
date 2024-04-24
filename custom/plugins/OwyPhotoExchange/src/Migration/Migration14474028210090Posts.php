<?php declare(strict_types=1);

namespace OwyPhotoExchange\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration14474028210090Posts extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 14474028210090;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE IF NOT EXISTS `photo_exchange_post` (
              `id` BINARY(16) NOT NULL,
              `category` BINARY(16) NULL,
              `customer` BINARY(16) NULL,
              `headline`  VARCHAR(200) NULL,
              `body` LONGTEXT,
              `imgages` VARCHAR(200),
              `status`  VARCHAR(100) NULL,   
              `is_active` BOOLEAN DEFAULT true,                             
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) NULL,
              PRIMARY KEY (`id`),
              CONSTRAINT `fk.photo_exchange_post.category` FOREIGN KEY (`category`) REFERENCES `photo_exchange_category` (`id`),
              CONSTRAINT `fk.photo_exchange_post.customer` FOREIGN KEY (`customer`) REFERENCES `customer` (`id`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
