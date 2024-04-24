<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalTime;

use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Context;

class ProductRentalTimeCollector extends RentalTimeCollector
{
    /**
     * @throws \Exception
     *
     * @return array|RentalTimeCollection[]
     */
    public function collectRentalTimes(
        Cart $cart,
        RentalProductEntity $rentalProductEntity,
        string $productId,
        Context $context
    ) {
        $data = [];
        $cartData = $this->collectDataFromCartForProduct($cart, $productId);
        if ($cartData !== []) {
            $data[] = $cartData;
        }

        $orderData = $this->collectDataFromOrdersForProduct($productId, $context);
        if ($orderData !== []) {
            $data[] = $orderData;
        }

        $blockedData = $this->collectDataFromBlockedPeriodsForProduct($productId, $context);
        if ($blockedData !== []) {
            $data[] = $blockedData;
        }

        if (!empty($data)) {
            $data = array_merge(...array_values($data));
        }

        return self::createRentalTimeCollection($data);
    }

    /**
     *
     * @throws \Exception
     * @return array
     */
    private function collectDataFromOrdersForProduct(
        string $productId,
        ?Context $context = null
    ) {
        $rentStart = new \DateTime('now', new \DateTimeZone('UTC'));
        $rentStart->setTime(0, 0, 0);

        $rentEnd = new \DateTime('now', new \DateTimeZone('UTC'));
        $rentEnd->setTime(23, 59, 59);
        $rentEnd->modify('+2 year');

        if (!$context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        return $this->collectDataFromOrders($productId, $rentStart->format('c'), $rentEnd->format('c'), $context);
    }

    /**
     * @return LineItem[]
     */
    private function collectDataFromCartForProduct(Cart $cart, string $productId)
    {
        $productCartLineItems = $this->collectDataFromCart($cart, $productId);

        return $productCartLineItems->getElements();
    }

    /**
     * @return array
     */
    private function collectDataFromBlockedPeriodsForProduct(string $productId, Context $context)
    {
        return $this->collectDataFromBlockedPeriods($productId, $context);
    }
}
