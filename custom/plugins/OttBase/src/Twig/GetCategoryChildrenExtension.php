<?php declare(strict_types=1);

namespace Ott\Base\Twig;

use Doctrine\DBAL\Connection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GetCategoryChildrenExtension extends AbstractExtension
{
    public function __construct(
        private readonly Connection $connection
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getCategoryChildren', fn (string $categoryId, string $languageId): array => $this->getCategoryChildren($categoryId, $languageId)),
        ];
    }

    public function getCategoryChildren(string $categoryId, string $languageId): array
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(c.id)) AS id, LOWER(HEX(c.media_id)) AS mediaId, ct.name, ct.link_type AS linkType,
                   ct.internal_link AS internalLink, ct.external_link As externalLink,
                   JSON_UNQUOTE(JSON_EXTRACT(ct.custom_fields, '$.ott_category_size')) AS ottCategorySize
            FROM category c
            JOIN category_translation ct ON ct.category_id = c.id
            WHERE c.parent_id = UNHEX(:categoryId) AND ct.language_id = UNHEX(:languageId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('categoryId', $categoryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);

        return $preparedStatement->execute()->fetchAllAssociative();
    }
}
