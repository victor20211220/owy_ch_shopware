<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProductBail;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RentalProductBailPriceCalculator
{
    private readonly RentalProductBailPriceDefinitionBuilder $rentalProductBailPriceDefinitionBuilder;

    private readonly QuantityPriceCalculator $priceCalculator;

    public function __construct(
        RentalProductBailPriceDefinitionBuilder $rentalProductBailPriceDefinitionBuilder,
        QuantityPriceCalculator $priceCalculator
    ) {
        $this->rentalProductBailPriceDefinitionBuilder = $rentalProductBailPriceDefinitionBuilder;
        $this->priceCalculator = $priceCalculator;
    }

    public function calculateRentalProductBailLineItemPrice(
        array $bail,
        LineItem $rentalProductBailLineItem,
        LineItem $rentalProductLineItem,
        SalesChannelContext $context
    ): void {
        $definition = $this->rentalProductBailPriceDefinitionBuilder->build(
            $bail,
            $rentalProductBailLineItem,
            $rentalProductLineItem,
            $context
        );
        $calculated = $this->priceCalculator->calculate($definition, $context);
        $rentalProductBailLineItem->setPrice($calculated);
        $rentalProductBailLineItem->setPriceDefinition($definition);
    }
}
