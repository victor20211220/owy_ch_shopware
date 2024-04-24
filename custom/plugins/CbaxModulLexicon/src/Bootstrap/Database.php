<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Bootstrap;

use Doctrine\DBAL\Connection;

class Database
{
    public function updateDatabaseTables($services)
    {
        /** @var Connection $connection  */
        $connection = $services['connectionService'];

        $tableName  = 'product';
        $columnName = 'cbax_lexicon_entry';

        if (!$this->columnExist($tableName, $columnName, $connection)) {
            $connection->executeStatement("ALTER TABLE `$tableName` ADD COLUMN `$columnName` BINARY(16) NULL");
        }
		
		$tableName  = 'cbax_lexicon_entry';
        $columnName = 'product_template';
		
		if (!$this->columnExist($tableName, $columnName, $connection)) {
            $connection->executeStatement("ALTER TABLE `$tableName` ADD COLUMN `$columnName` varchar(30) NULL AFTER `product_layout`");
        }

        // Update 3.1.0 CMS Introduction
        if (!$this->columnExist('cbax_lexicon_entry', 'media2_id', $connection)) {
            $connection->executeStatement(
                "ALTER TABLE cbax_lexicon_entry ADD COLUMN media2_id BINARY(16) DEFAULT NULL AFTER attribute2;"
            );
            $connection->executeStatement(
                "UPDATE cbax_lexicon_entry SET media2_id = UNHEX(attribute2) WHERE attribute2 IS NOT NULL AND media2_id IS NULL;"
            );
        }

        if (!$this->columnExist('cbax_lexicon_entry', 'media3_id', $connection)) {
            $connection->executeStatement(
                "ALTER TABLE cbax_lexicon_entry ADD COLUMN media3_id BINARY(16) DEFAULT NULL AFTER attribute3;"
            );
            $connection->executeStatement(
                "UPDATE cbax_lexicon_entry SET media3_id = UNHEX(attribute3) WHERE attribute3 IS NOT NULL AND media3_id IS NULL;"
            );
        }
    }

    public function removeDatabaseTables(array $services): void
    {
        /** @var Connection $connection  */
        $connection = $services['connectionService'];

        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_lexicon_sales_channel`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_lexicon_product`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_lexicon_entry_translation`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_lexicon_entry`');

        $tableName  = 'product';
        $columnName = 'cbax_lexicon_entry';

        if ($this->columnExist($tableName, $columnName, $connection)) {
            $connection->executeStatement("ALTER TABLE `$tableName` DROP COLUMN `$columnName`");
        }
    }

    /**
     * Prüft, ob eine Spalte in der Datenbank existiert
     */
    public function columnExist(string $tableName, string $columnName, Connection $connection): bool
    {
        $sql = "SHOW COLUMNS FROM " . $tableName . " LIKE '" . $columnName ."'";

        return !empty($connection->fetchAssociative($sql));
    }

    public function deleteColumn(string $tableName, string $columnName, Connection $connection) {
        if ($this->columnExist($tableName, $columnName, $connection)) {
            // delete Column
            $connection->executeStatement("ALTER TABLE `" . $tableName . "`  DROP COLUMN `" . $columnName . "`;");
        }
    }

}
