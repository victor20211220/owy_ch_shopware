<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(OrderDeliveryStoreEntity $entity)
 * @method void              set(string $key, OrderDeliveryStoreEntity $entity)
 * @method OrderDeliveryStoreEntity[]    getIterator()
 * @method OrderDeliveryStoreEntity[]    getElements()
 * @method OrderDeliveryStoreEntity|null get(string $key)
 * @method OrderDeliveryStoreEntity|null first()
 * @method OrderDeliveryStoreEntity|null last()
 */
class OrderDeliveryStoreCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderDeliveryStoreEntity::class;
    }
}
