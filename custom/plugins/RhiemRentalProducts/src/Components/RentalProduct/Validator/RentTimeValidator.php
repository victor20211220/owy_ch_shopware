<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Validator;

use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductInvalidRentOffsetException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductMaxRentTimeExceededException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductMinRentTimeNotReachedException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

class RentTimeValidator extends Validator implements RentalProductModeInterface
{
    /**
     * @throws RentalProductInvalidRentOffsetException
     * @throws RentalProductMaxRentTimeExceededException
     * @throws RentalProductMinRentTimeNotReachedException
     */
    public function validate(array $params): void
    {
        /** @var RentalProductEntity $rentalProduct */
        $rentalProduct = $params['rentalProduct'];

        $minRentPeriod = (int) ($rentalProduct->getMinPeriod());
        $maxRentPeriod = (int) ($rentalProduct->getMaxPeriod());
        $rentOffsetPeriod = (int) ($rentalProduct->getOffset());

        if (empty($minRentPeriod) && empty($maxRentPeriod) && empty($rentOffsetPeriod)) {
            return;
        }

        /** @var LineItem $lineItem */
        $lineItem = $params['lineItem'];

        /** @var RentalTime $rentalTime */
        $rentalTime = $lineItem->getPayloadValue('rentalProduct')['rentalTime'];
        $offsetTime = new \DateTime('now', new \DateTimeZone($rentalTime->getTimezone()));
        $rentPerUnit = 0;
        if ($rentalProduct->getMode() === self::DAYRENT) {
            $rentPerUnit = (int) $rentalTime->getPeriod()->getEndDate()->diff(
                $rentalTime->getPeriod()->getStartDate()
            )->format('%a');
            ++$rentPerUnit;
            $offsetTime->setTime(0, 0, 0);
            $offsetTime->modify('+' . $rentOffsetPeriod . ' day');
        }

        if ($minRentPeriod && $rentPerUnit < $minRentPeriod) {
            throw new RentalProductMinRentTimeNotReachedException(
                $lineItem->getReferencedId(),
                $lineItem->getLabel()
            );
        }

        if ($maxRentPeriod && $rentPerUnit > $maxRentPeriod) {
            throw new RentalProductMaxRentTimeExceededException(
                $lineItem->getReferencedId(),
                $lineItem->getLabel()
            );
        }

        if ($rentOffsetPeriod !== 0 && $rentalTime->getStartDate() < $offsetTime) {
            throw new RentalProductInvalidRentOffsetException(
                $lineItem->getReferencedId(),
                $lineItem->getLabel()
            );
        }
    }
}
