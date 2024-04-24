<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

final class TagModule extends BaseModule
{
    private array $tagIdCache = [];

    public function selectTagId(string $name): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->tagIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(id)) FROM tag WHERE name = :name
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->tagIdCache[$name] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->tagIdCache[$name] : (string) $result;
    }

    public function storeTag(
        string $id,
        string $name
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO tag (id, name, created_at)
            VALUES (UNHEX(:id), :name, NOW())
            ON DUPLICATE KEY UPDATE name = :name, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }
}
