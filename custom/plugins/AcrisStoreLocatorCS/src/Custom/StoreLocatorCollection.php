<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(StoreLocatorEntity $entity)
 * @method void              set(string $key, StoreLocatorEntity $entity)
 * @method StoreLocatorEntity[]    getIterator()
 * @method StoreLocatorEntity[]    getElements()
 * @method StoreLocatorEntity|null get(string $key)
 * @method StoreLocatorEntity|null first()
 * @method StoreLocatorEntity|null last()
 */
class StoreLocatorCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return StoreLocatorEntity::class;
    }
}
