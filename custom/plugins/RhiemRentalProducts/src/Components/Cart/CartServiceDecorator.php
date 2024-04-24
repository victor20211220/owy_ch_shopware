<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\Cart;

use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\CartPersister;
use Rhiem\RhiemRentalProducts\Components\Cart\CartHelper;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemRemoveRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemUpdateRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class CartServiceDecorator extends CartService
{
    private readonly CartService $cartService;

    private readonly CartHelper $cartHelper;

    private readonly EntityRepository $rentalProductRepository;

    /**
     * @internal
     */
    public function __construct(
        CartService $cartService,
        CartPersister $persister,
        EventDispatcherInterface $eventDispatcher,
        CartCalculator $calculator,
        AbstractCartLoadRoute $loadRoute,
        AbstractCartDeleteRoute $deleteRoute,
        AbstractCartItemAddRoute $itemAddRoute,
        AbstractCartItemUpdateRoute $itemUpdateRoute,
        AbstractCartItemRemoveRoute $itemRemoveRoute,
        AbstractCartOrderRoute $orderRoute,
        CartHelper $cartHelper,
        EntityRepository $rentalProductRepository
    ) {
        $this->cartService = $cartService;
        $this->cartHelper = $cartHelper;
        $this->rentalProductRepository = $rentalProductRepository;

        parent::__construct(
            $persister,
            $eventDispatcher,
            $calculator,
            $loadRoute,
            $deleteRoute,
            $itemAddRoute,
            $itemUpdateRoute,
            $itemRemoveRoute,
            $orderRoute
        );
    }

    public function getCart(
        string $token,
        SalesChannelContext $context,
        bool $caching = true,
        bool $taxed = false
    ): Cart {
        $cart = $this->cartService->getCart($token, $context, $caching, $taxed);
        $rentalInformation = [];

        $rentalLineItems = $cart->getLineItems()->filter(static fn(LineItem $lineItem) => $lineItem->getType() === "rentalProduct");

        if ($rentalLineItems->count() > 0) {
            $rentalInformation['containsRentalItems'] = true;
            $rentalInformation['durations'] = $this->getDurations($rentalLineItems, $context->getContext());
            $rentalInformation['currentRentalTime'] = $this->cartHelper->getCurrentRentalTime($cart);
        }

        $cart->addArrayExtension('rentalInformation', $rentalInformation);

        return $cart;
    }

    private function getDurations(LineItemCollection $rentalLineItems, Context $context)
    {
        $duration = [
            'min' => null,
            'max' => null
        ];

        $productIds = $rentalLineItems->map(static fn(LineItem $lineItem) => $lineItem->getReferencedId());

        $rentalProducts = $this->getRentalProducts(array_values($productIds), $context);

        foreach($rentalProducts as $rentalProduct) {
            $duration['min'] = $rentalProduct->getMinPeriod() > $duration['min'] ? $rentalProduct->getMinPeriod() : $duration['min'];
            $duration['max'] = !$duration['max'] || $rentalProduct->getMaxPeriod() < $duration['max'] ? $rentalProduct->getMaxPeriod() : $duration['max'];
        }

        return $duration;
    }

    /**
     * @return RentalProductEntity|null
     */
    private function getRentalProducts(array $productIds, Context $context)
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsAnyFilter('productId', $productIds))
            ->addFilter(new EqualsFilter('active', true));

        return $this->rentalProductRepository->search($criteria, $context);
    }
}
