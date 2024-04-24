<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\Promotion\ScopePackager;

use Rhiem\RhiemRentalProducts\Components\RentalProduct\LineItem\RentalProductLineItemFactory;
use Rhiem\RhiemRentalProducts\Components\RentalProductBail\LineItem\RentalProductBailLineItemFactory;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Exception\InvalidQuantityException;
use Shopware\Core\Checkout\Cart\Exception\LineItemNotStackableException;
use Shopware\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Shopware\Core\Checkout\Cart\LineItem\Group\LineItemQuantityCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItemFlatCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItemQuantitySplitter;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Shopware\Core\Checkout\Cart\Rule\LineItemScope;
use Shopware\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Shopware\Core\Checkout\Promotion\Cart\Discount\DiscountPackage;
use Shopware\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Shopware\Core\Checkout\Promotion\Cart\Discount\DiscountPackager;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartScopeDiscountPackagerDecorator extends DiscountPackager
{
    private readonly DiscountPackager $cartScopeDiscountPackager;

    private readonly LineItemQuantitySplitter $lineItemQuantitySplitter;

    public function __construct(
        DiscountPackager $cartScopeDiscountPackager,
        LineItemQuantitySplitter $lineItemQuantitySplitter
    ) {
        $this->cartScopeDiscountPackager = $cartScopeDiscountPackager;
        $this->lineItemQuantitySplitter = $lineItemQuantitySplitter;
    }

    public function getDecorated(): DiscountPackager
    {
        return $this->cartScopeDiscountPackager;
    }

    public function getMatchingItems(
        DiscountLineItem $discount,
        Cart $cart,
        SalesChannelContext $context
    ): DiscountPackageCollection {
        $allRentalProductLineItems = $cart->getLineItems()->filterType(RentalProductLineItemFactory::TYPE);
        $discountPackageCollection = $this->cartScopeDiscountPackager->getMatchingItems($discount, $cart, $context);

        if ($allRentalProductLineItems->count() !== 0) {
            $allRentalBailLineItems = $cart->getLineItems()->filterType(RentalProductBailLineItemFactory::TYPE);
            if ($allRentalBailLineItems->count() !== 0) {
                $bailItems = $allRentalBailLineItems->getElements();
                foreach ($bailItems as $bailItem) {
                    $bailItem->setStackable(true);
                    $allRentalProductLineItems->add($bailItem);
                }
            }

            $singleItems = $this->splitQuantities($allRentalProductLineItems, $context);

            $priceDefinition = $discount->getPriceDefinition();
            $foundItems = [];
            foreach ($singleItems as $cartLineItem) {
                if ($this->isRulesFilterValid($cartLineItem, $priceDefinition, $context)) {
                    $item = new LineItemQuantity(
                        $cartLineItem->getId(),
                        $cartLineItem->getQuantity()
                    );

                    $foundItems[] = $item;
                }
            }

            if ($foundItems !== []) {
                $discountPackage = $discountPackageCollection->first();

                if (!$discountPackage) {
                    $package = new DiscountPackage(new LineItemQuantityCollection($foundItems));
                    $discountPackageCollection->add($package);
                } else {
                    $lineItemQuantityCollection = $discountPackage->getMetaData();
                    foreach ($foundItems as $fountItem) {
                        $lineItemQuantityCollection->add($fountItem);
                    }
                }
            }
        }

        return $discountPackageCollection;
    }

    private function isRulesFilterValid(
        LineItem $item,
        PriceDefinitionInterface $priceDefinition,
        SalesChannelContext $context
    ): bool {
        // if the price definition doesnt allow filters,
        // then return valid for the item
        if (!method_exists($priceDefinition, 'getFilter')) {
            return true;
        }

        /** @var Rule|null $filter */
        $filter = $priceDefinition->getFilter();

        // if the definition exists, but is empty
        // this means we have no restrictions and thus its valid
        if (!$filter instanceof Rule) {
            return true;
        }

        // if our price definition has a filter rule
        // then extract it, and check if it matches
        $scope = new LineItemScope($item, $context);
        return $filter->match($scope);
    }

    /**
     * @throws InvalidQuantityException
     * @throws LineItemNotStackableException
     */
    private function splitQuantities(
        LineItemCollection $cartItems,
        SalesChannelContext $context
    ): LineItemFlatCollection {
        $items = [];

        foreach ($cartItems as $item) {
            for ($i = 1; $i <= $item->getQuantity(); ++$i) {
                $tmpItem = $this->lineItemQuantitySplitter->split($item, 1, $context);

                $items[] = $tmpItem;
            }
        }

        return new LineItemFlatCollection($items);
    }
}
