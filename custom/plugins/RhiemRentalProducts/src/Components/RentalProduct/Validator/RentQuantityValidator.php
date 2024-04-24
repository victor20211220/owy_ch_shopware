<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Validator;

use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductOutOfStockException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductQuantityReduceException;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

class RentQuantityValidator extends Validator
{
    /**
     * @throws RentalProductOutOfStockException
     * @throws RentalProductQuantityReduceException
     */
    public function validate(array $params): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $params['lineItem'];
        $rentalProductPayload = $lineItem->getPayloadValue('rentalProduct');

        /** @var RentalTime $rentalTime */
        $rentalTime = $rentalProductPayload['rentalTime'];

        if ($rentalProductPayload['maxAvailable'] <= 0) {
            throw new RentalProductOutOfStockException(
                $lineItem->getReferencedId(),
                $lineItem->getLabel()
                . ' (' . $rentalTime->getStartDate()->format('d.m.Y')
                . ' - ' . $rentalTime->getEndDate()->format('d.m.Y') . ')'
            );
        } elseif ($lineItem->getQuantity() > $rentalProductPayload['maxAvailable']) {
            throw new RentalProductQuantityReduceException(
                $lineItem->getReferencedId(),
                $lineItem->getLabel()
                . ' (' . $rentalTime->getStartDate()->format('d.m.Y')
                . ' - ' . $rentalTime->getEndDate()->format('d.m.Y') . ')'
            );
        }
    }
}
