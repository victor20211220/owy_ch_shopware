<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalTime;

use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Context;

class LineItemRentalTimeCollector extends RentalTimeCollector
{
    /**
     * @throws \Exception
     *
     * @return array|RentalTimeCollection[]
     */
    public function collectRentalTimes(
        Cart $cart,
        LineItem $lineItem,
        RentalProductEntity $rentalProductEntity,
        Context $context
    ) {
        $data = [];
        $cartData = $this->collectDataFromCartForLineItem($cart, $lineItem);
        if ($cartData !== []) {
            $data[] = $cartData;
        }

        $orderData = $this->collectDataFromOrdersForLineItem($lineItem, $rentalProductEntity, $context);
        if ($orderData !== []) {
            $data[] = $orderData;
        }

        $blockedData = $this->collectDataFromBlockedPeriodsForLineItem($lineItem, $context);
        if ($blockedData !== []) {
            $data[] = $blockedData;
        }

        if (!empty($data)) {
            $data = array_merge(...array_values($data));
        }

        return self::createRentalTimeCollection($data);
    }

    /**
     * @return array
     */
    protected function collectDataFromOrdersForLineItem(
        LineItem $lineItem,
        RentalProductEntity $rentalProductEntity,
        ?Context $context = null
    ) {
        /** @var RentalTime $rentalTime */
        $rentalTime = $lineItem->getPayloadValue('rentalProduct')['rentalTime'];
        $rentStart = clone $rentalTime->getStartDate();
        $rentEnd = clone $rentalTime->getEndDate();

        $buffer = $rentalProductEntity->getBuffer();
        $unit = 'day';
        if ($buffer) {
            if ($rentalProductEntity->getMode() === self::DAYRENT) {
                $unit = 'day';
            }

            $rentStart->modify('-' . (int) $buffer . ' ' . $unit);
            $rentEnd->modify('+' . (int) $buffer . ' ' . $unit);
        }

        if (!$context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        return $this->collectDataFromOrders(
            $lineItem->getReferencedId(),
            $rentStart->format('c'),
            $rentEnd->format('c'),
            $context
        );
    }

    /**
     * @return LineItem[]
     */
    private function collectDataFromCartForLineItem(Cart $cart, LineItem $lineItem)
    {
        $rentalLineItems = parent::collectDataFromCart($cart, $lineItem->getReferencedId());
        $lineItemId = $lineItem->getId();

        return $rentalLineItems->filter(
            static fn(LineItem $cartLineItem) => $cartLineItem->getId() !== $lineItemId
        )->getElements();
    }

    /**
     * @return array
     */
    private function collectDataFromBlockedPeriodsForLineItem(LineItem $lineItem, Context $context)
    {
        return $this->collectDataFromBlockedPeriods($lineItem->getReferencedId(), $context);
    }
}
