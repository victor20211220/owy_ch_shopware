<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Subscriber;

use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class FrontendCheckout implements EventSubscriberInterface
{
    private readonly EntityRepository $orderLineItemRepository;

    public function __construct(EntityRepository $orderLineItemRepo)
    {
        $this->orderLineItemRepository = $orderLineItemRepo;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
            CartConvertedEvent::class => 'onConvertToOrder'
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $orderLineItems = $event->getOrder()->getLineItems()->getElements();

        foreach ($orderLineItems as $orderLineItem) {
            if (isset($orderLineItem->getPayload()['rentalProduct'])) {
                $this->orderLineItemRepository->update(
                    [
                        [
                            'id' => $orderLineItem->getId(),
                            'productId' => $orderLineItem->getReferencedId()
                        ],
                    ],
                    $event->getContext()
                );
            }
        }
    }


    public function onConvertToOrder(CartConvertedEvent $event): void
    {
        $convertedCart = $event->getConvertedCart();
        $lineItems = $convertedCart['lineItems'];
        $orderHasRentalProduct = false;

        foreach ($lineItems as &$lineItem) {
            if (!empty($lineItem['payload']['rentalProduct'])) {
                $orderHasRentalProduct = true;

                if ($lineItem['payload']['rentalProduct']['rentalTime'] instanceof RentalTime) {
                    $lineItem['payload']['rentalProduct']['rentalTime'] = $lineItem['payload']['rentalProduct']['rentalTime']->toJson();
                }
            }
        }

        if ($orderHasRentalProduct) {
            $convertedCart['lineItems'] = $lineItems;
            $event->setConvertedCart($convertedCart);
        }
    }
}
