<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Service;

use Doctrine\DBAL\Connection;
use Ott\SelectlineImport\Dbal\Entity\ImportMessageCollection;
use Ott\SelectlineImport\Dbal\Entity\ImportMessageEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

class ImportMessageManager
{
    private string $file;
    private string $type;
    private Connection $connection;
    private EntityRepository $importMessageRepository;

    public function __construct(Connection $connection, EntityRepository $importMessageRepository)
    {
        $this->connection = $connection;
        $this->importMessageRepository = $importMessageRepository;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function generate(array $workload): void
    {
        $date = date('Y-m-d H:i:s');
        $this->connection->insert(ImportMessageEntity::TABLE, [
            'id'         => hex2bin(Uuid::randomHex()),
            'file'       => $this->file,
            'type'       => $this->type,
            'workload'   => json_encode($workload),
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    public function get(int $limit = 1): ImportMessageCollection
    {
        $statement = <<<'SQL'
            SELECT HEX(id) as id, workload, type, file, created_at, updated_at FROM %s ORDER BY created_at ASC LIMIT :limit
            SQL;

        $preparedStatement = $this->connection->prepare(sprintf($statement, ImportMessageEntity::TABLE));
        $preparedStatement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $result = $preparedStatement->execute()->fetchAll();

        $collection = new ImportMessageCollection();
        foreach ($result as $message) {
            $importMessage = new ImportMessageEntity();
            $importMessage->setId($message['id']);
            $importMessage->setCreatedAt(new \DateTime($message['created_at']));
            $importMessage->setUpdatedAt(new \DateTime($message['updated_at']));
            $importMessage
                ->setType($message['type'])
                ->setFile($message['file'])
                ->setWorkload(json_decode($message['workload'], true))
            ;

            $collection->add($importMessage);
        }

        return $collection;
    }

    public function delete(string $id): void
    {
        $statement = <<<'SQL'
            DELETE FROM %s WHERE id = UNHEX('%s')
            SQL;
        $this->connection->exec(sprintf($statement, ImportMessageEntity::TABLE, $id));
    }
}
