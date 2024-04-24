<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProductBail;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RentalProductBailPriceDefinitionBuilder
{
    public function build(
        array $bail,
        LineItem $rentalProductBailLineItem,
        LineItem $rentalProductLineItem,
        SalesChannelContext $context
    ): QuantityPriceDefinition {
        /** @var Price $price */
        $price = $bail['price']->first();
        $rentQuantity = $rentalProductLineItem->getQuantity();

        if ($context->getCurrency()->getId() === $price->getCurrencyId()) {
            $bailPrice = $price->getGross();
        } else {
            $bailPrice = $context->getCurrency()->getFactor() * $price->getGross();
        }

        return new QuantityPriceDefinition(
            $bailPrice * $rentQuantity,
            $context->buildTaxRules($bail['taxId']),
            1
        );
    }
}
