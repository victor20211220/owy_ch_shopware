<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Service;

use Doctrine\DBAL\Connection;
use Ott\SelectlineImport\Dbal\Entity\ImportPictureMessageCollection;
use Ott\SelectlineImport\Dbal\Entity\ImportPictureMessageEntity;
use Shopware\Core\Framework\Uuid\Uuid;

class ImportPictureMessageManager
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function generate(string $productId, array $workload): void
    {
        $date = date('Y-m-d H:i:s');
        $this->connection->insert(ImportPictureMessageEntity::TABLE, [
            'id'         => hex2bin(Uuid::randomHex()),
            'product_id' => hex2bin($productId),
            'workload'   => json_encode($workload),
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    public function get(int $limit = 1): ImportPictureMessageCollection
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) as id, LOWER(HEX(product_id)) as product_id, workload, created_at, updated_at FROM %s ORDER BY created_at ASC LIMIT :limit
            SQL;

        $preparedStatement = $this->connection->prepare(sprintf($statement, ImportPictureMessageEntity::TABLE));
        $preparedStatement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $result = $preparedStatement->execute()->fetchAll();

        $collection = new ImportPictureMessageCollection();
        foreach ($result as $message) {
            $importMessage = new ImportPictureMessageEntity();
            $importMessage->setId($message['id']);
            $importMessage->setProductId($message['product_id']);
            $importMessage->setCreatedAt(new \DateTime($message['created_at']));
            $importMessage->setUpdatedAt(new \DateTime($message['updated_at']));
            $importMessage->setWorkload(json_decode($message['workload'], true));

            $collection->add($importMessage);
        }

        return $collection;
    }

    public function delete(string $id): void
    {
        $statement = <<<'SQL'
            DELETE FROM %s WHERE id = UNHEX('%s')
            SQL;
        $this->connection->exec(sprintf($statement, ImportPictureMessageEntity::TABLE, $id));
    }
}
