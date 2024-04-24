<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1572516630lexicon extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1572516630;
    }

    public function update(Connection $connection): void
    {
		 $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `cbax_lexicon_entry` (
				`id` BINARY(16) NOT NULL,
				`impressions` int(11) DEFAULT 0,
				`date` datetime DEFAULT NULL,
				`link` varchar(255) DEFAULT NULL,
				`link_target` varchar(255) DEFAULT NULL,
				`listing_type` varchar(30) DEFAULT NULL,
				`product_stream_id` BINARY(16) DEFAULT NULL,
				`product_layout` varchar(30) DEFAULT NULL,
				`product_template` varchar(30) DEFAULT NULL,
				`product_slider_width` varchar(30) DEFAULT NULL,
				`product_sorting` varchar(30) DEFAULT NULL,
				`product_limit` int(11) DEFAULT 0,
				`attribute2` varchar(255) DEFAULT NULL,
				`attribute3` varchar(255) DEFAULT NULL,
                `media2_id` BINARY(16) DEFAULT NULL,
				`media3_id` BINARY(16) DEFAULT NULL,
				`saleschannels` BINARY(16) NULL,
				`created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

		$connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `cbax_lexicon_entry_translation` (
				`cbax_lexicon_entry_id` BINARY(16) NOT NULL,
				`language_id` BINARY(16) NOT NULL,
				`title` varchar(255) NOT NULL,
				`keyword` varchar(255) NOT NULL,
				`description` text DEFAULT NULL,
				`description_long` text DEFAULT NULL,
				`link_description` varchar(255) DEFAULT NULL,
				`meta_title` varchar(255) DEFAULT NULL,
				`meta_keywords` varchar(255) DEFAULT NULL,
				`meta_description` varchar(500) DEFAULT NULL,
				`headline` varchar(255) DEFAULT NULL,
				`attribute1` TEXT DEFAULT NULL,
				`attribute4` varchar(255) DEFAULT NULL,
				`attribute5` varchar(255) DEFAULT NULL,
				`attribute6` varchar(255) DEFAULT NULL,
				`created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`cbax_lexicon_entry_id`, `language_id`),
				INDEX(`keyword`),
				CONSTRAINT `fk.cbax_lexicon_entry_translation.cbax_lexicon_entry_id` FOREIGN KEY (`cbax_lexicon_entry_id`)
					REFERENCES `cbax_lexicon_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				CONSTRAINT `fk.cbax_lexicon_entry_translation.language_id` FOREIGN KEY (`language_id`)
					REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

		$connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `cbax_lexicon_product` (
				`cbax_lexicon_entry_id` BINARY(16) NOT NULL,
				`product_id` BINARY(16) NOT NULL,
				`product_version_id` BINARY(16) NOT NULL,
				`position` int(1) DEFAULT 0,
				PRIMARY KEY (`cbax_lexicon_entry_id`, `product_id`, `product_version_id`),
				INDEX(`product_id`),
				CONSTRAINT `fk.cbax_lexicon_product.cbax_lexicon_entry_id` FOREIGN KEY (`cbax_lexicon_entry_id`)
					REFERENCES `cbax_lexicon_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				CONSTRAINT `fk.cbax_lexicon_product.product_id` FOREIGN KEY (`product_id`, `product_version_id`)
					REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

		$connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `cbax_lexicon_sales_channel` (
				`id` BINARY(16) NOT NULL,
				`cbax_lexicon_entry_id` BINARY(16) NOT NULL,
				`sales_channel_id` BINARY(16) NOT NULL,
				`created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`id`, `cbax_lexicon_entry_id`),
				CONSTRAINT `fk.cbax_lexicon_sales_channel.cbax_lexicon_entry_id` FOREIGN KEY (`cbax_lexicon_entry_id`)
					REFERENCES `cbax_lexicon_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.cbax_lexicon_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
