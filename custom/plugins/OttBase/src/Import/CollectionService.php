<?php declare(strict_types=1);

namespace Ott\Base\Import;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class CollectionService
{
    public static function buildCollection(array $entities, string $collectionClass): EntityCollection
    {
        /** @var EntityCollection $collection */
        /** @var Entity $entity */
        $collection = new $collectionClass();
        foreach ($entities as $key => $entity) {
            $entity->setUniqueIdentifier((string) $key);
            $collection->add($entity);
        }

        return $collection;
    }
}
