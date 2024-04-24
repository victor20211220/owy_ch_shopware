<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Validator;

use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductInvalidModeException;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

class RentModeValidator extends Validator
{
    /**
     * @throws RentalProductInvalidModeException
     */
    public function validate(array $params): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $params['lineItem'];
        /** @var RentalProductEntity $rentalProduct */
        $rentalProduct = $params['rentalProduct'];
        /** @var RentalTime $lineItemRentalTime */
        $lineItemRentalTime = $lineItem->getPayloadValue('rentalProduct')['rentalTime'];

        if ($lineItemRentalTime->getMode() !== $rentalProduct->getMode()) {
            throw new RentalProductInvalidModeException(
                $lineItem->getReferencedId(),
                $lineItem->getLabel()
                . ' (' . $lineItemRentalTime->getStartDate()->format('d.m.Y') . ' - '
                . $lineItemRentalTime->getEndDate()->format('d.m.Y') . ')'
            );
        }
    }
}
