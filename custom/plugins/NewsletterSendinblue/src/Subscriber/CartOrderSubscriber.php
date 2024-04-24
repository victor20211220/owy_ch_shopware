<?php

namespace NewsletterSendinblue\Subscriber;

use NewsletterSendinblue\Service\Cart\CartEventProducer;
use NewsletterSendinblue\Service\ConfigService;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartOrderSubscriber implements EventSubscriberInterface
{    
    /**
     * @var ConfigService
    */
    private $configService;

    /**
     * @var CartEventProducer
    */
    private $cartEventProducer;

    public function __construct(ConfigService $configService, CartEventProducer $cartEventProducer)
    {
        $this->configService = $configService;
        $this->cartEventProducer = $cartEventProducer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $this->configService->setSalesChannelId($event->getSalesChannelId());
        if ($this->configService->isAutoSyncEnabled()) {
            $this->cartEventProducer->createTransactionalOrder(
                $event->getOrder(), $event->getContext(), $event->getSalesChannelId()
            );
        }
    }
}
