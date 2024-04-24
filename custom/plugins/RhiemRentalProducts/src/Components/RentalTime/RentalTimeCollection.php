<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalTime;

use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Struct\Collection;

class RentalTimeCollection extends Collection implements RentalProductModeInterface
{
    /**
     * @param string $productId
     * @param array  $elements
     */
    public static function createRentalTimeCollection($productId, $elements = [])
    {
        return [$productId => new self($elements)];
    }

    /**
     * @return int
     */
    public function getBlockedQuantityForRentalTimePeriod(LineItem $lineItem, RentalProductEntity $rentalProductEntity)
    {
        $interval = '1 DAY';
        $unit = 'day';
        /** @var RentalTime $lineItemRentalTime */
        $lineItemRentalTime = $lineItem->getPayloadValue('rentalProduct')['rentalTime'];
        $quantity = 0;
        $buffer = $rentalProductEntity->getBuffer();

        $lineItemPeriod = clone $lineItemRentalTime->getPeriod();
        if ($buffer) {
            $lineItemPeriod = $lineItemPeriod->moveEndDate('+' . (int) $buffer . ' ' . $unit);
        }

        foreach ($lineItemPeriod->getDatePeriod($interval) as $datetime) {
            $quantityPerUnit = 0;
            /** @var RentalTime $rentalTime */
            foreach ($this->elements as $rentalTime) {
                $period = clone $rentalTime->getPeriod();
                if ($buffer && $rentalTime->getType() !== 'block') {
                    $period = $period->moveEndDate('+' . (int) $buffer . ' ' . $unit);
                }

                if ($period->contains($datetime)) {
                    $quantityPerUnit += $rentalTime->getQuantity();
                }
            }

            $quantity = max($quantity, $quantityPerUnit);
        }

        return $quantity;
    }

    /**
     * @return array
     */
    public function createRentTimes(RentalProductEntity $rentalProductEntity)
    {
        $interval = '1 DAY';
        $unit = 'day';

        $buffer = $rentalProductEntity->getBuffer();
        $rents = [];
        /** @var RentalTime $rentalTime */
        foreach ($this->elements as $rentalTime) {
            $period = clone $rentalTime->getPeriod();
            if ($buffer && $rentalTime->getType() !== 'block') {
                $period = $period->moveStartDate('-' . (int) $buffer . ' ' . $unit);
                $period = $period->moveEndDate('+' . (int) $buffer . ' ' . $unit);
            }

            /** @var \DateTimeImmutable $datetime */
            foreach ($period->getDatePeriod($interval) as $datetime) {
                if (empty($rents[$datetime->format('c')])) {
                    $rents[$datetime->format('c')] = [];
                    $rents[$datetime->format('c')]['rented'] = 0;
                }

                $rents[$datetime->format('c')]['rented'] += $rentalTime->getQuantity();
            }
        }

        foreach (array_keys($rents) as $key) {
            $rents[$key]['minAvailable'] = $rentalProductEntity->getOriginalStock() - $rents[$key]['rented'];
        }

        return $rents;
    }
}
