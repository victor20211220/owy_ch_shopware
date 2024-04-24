<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

final class MediaModule extends BaseModule
{
    private array $mediaIdCache = [];

    public function selectMediaId(string $name, string $extension): ?string
    {
        $result = '';
        if (!isset($this->mediaIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(id)) FROM media WHERE file_name = :name COLLATE utf8mb4_bin AND file_extension = :extension COLLATE utf8mb4_bin
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
            $preparedStatement->bindValue('extension', $extension, \PDO::PARAM_STR);
            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->mediaIdCache[$name] = $result;
            }
        }

        return $this->isCacheEnabled() ? $this->mediaIdCache[$name] : (string) $result;
    }

    public function selectMediaById(string $id): ?array
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) as id,
                   LOWER(HEX(user_id)) as user_id,
                   LOWER(HEX(media_folder_id)) as media_folder_id,
                   mime_type,
                   file_extension,
                   file_size,
                   meta_data,
                   file_name,
                   media_type,
                   thumbnails_ro,
                   private,
                   uploaded_at,
                   created_at,
                   updated_at
            FROM media
            WHERE id = UNHEX(:id)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetch();
        if (false === $result) {
            return null;
        }

        return $result;
    }
}
