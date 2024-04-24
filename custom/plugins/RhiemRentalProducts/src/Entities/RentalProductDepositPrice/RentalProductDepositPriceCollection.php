<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Entities\RentalProductDepositPrice;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                 add(RentalProductDepositPriceEntity $entity)
 * @method void                                 set(string $key, RentalProductDepositPriceEntity $entity)
 * @method RentalProductDepositPriceEntity[]    getIterator()
 * @method RentalProductDepositPriceEntity[]    getElements()
 * @method RentalProductDepositPriceEntity|null get(string $key)
 * @method RentalProductDepositPriceEntity|null first()
 * @method RentalProductDepositPriceEntity|null last()
 */
class RentalProductDepositPriceCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return RentalProductDepositPriceEntity::class;
    }
}
