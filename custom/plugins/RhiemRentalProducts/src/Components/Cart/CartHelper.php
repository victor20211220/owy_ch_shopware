<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;

class CartHelper
{
    /**
     * @param Cart   $context
     * @param string|null           $excludedId
     */
    public function getCurrentRentalTime(
        Cart $cart,
        string $excludedId = null
    ): ?RentalTime {
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== 'rentalProduct' || ($excludedId && $lineItem->getId() === $excludedId)) continue;

            if (isset($lineItem->getPayload()['rentalProduct']['rentalTime'])) {
                return $lineItem->getPayload()['rentalProduct']['rentalTime'];
            }
        }

        return null;
    }
}
