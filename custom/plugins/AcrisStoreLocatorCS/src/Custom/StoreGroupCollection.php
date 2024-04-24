<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(StoreGroupEntity $entity)
 * @method void              set(string $key, StoreGroupEntity $entity)
 * @method StoreGroupEntity[]    getIterator()
 * @method StoreGroupEntity[]    getElements()
 * @method StoreGroupEntity|null get(string $key)
 * @method StoreGroupEntity|null first()
 * @method StoreGroupEntity|null last()
 */
class StoreGroupCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return StoreGroupEntity::class;
    }
}
