<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Entities\RentalProduct;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                     add(RentalProductEntity $entity)
 * @method void                     set(string $key, RentalProductEntity $entity)
 * @method RentalProductEntity[]    getIterator()
 * @method RentalProductEntity[]    getElements()
 * @method RentalProductEntity|null get(string $key)
 * @method RentalProductEntity|null first()
 * @method RentalProductEntity|null last()
 */
class RentalProductCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return RentalProductEntity::class;
    }
}
