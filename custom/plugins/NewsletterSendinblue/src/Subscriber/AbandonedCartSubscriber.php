<?php

namespace NewsletterSendinblue\Subscriber;

use NewsletterSendinblue\Service\Cart\CartEventProducer;
use NewsletterSendinblue\Service\ConfigService;
use NewsletterSendinblue\Service\Cart\SendinblueCartProcessorService;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemRemovedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemAddedEVent;
use Shopware\Core\Checkout\Cart\Event\LineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemRemovedEvent;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class AbandonedCartSubscriber implements EventSubscriberInterface
{
    /**
     * @var CartEventProducer
     */
    private $cartEventProducer;

    /**
     * @var SendinblueCartProcessorService
     */
    private $cartProcessorService;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * AbandonedCartSubscriber constructor.
     *
     * @param CartEventProducer $cartEventProducer
     * @param SendinblueCartProcessorService $cartProcessorService
     * @param ConfigService $configService
     */
    public function __construct(
        CartEventProducer $cartEventProducer,
        SendinblueCartProcessorService $cartProcessorService,
        ConfigService $configService
    ) {
        $this->cartEventProducer = $cartEventProducer;
        $this->cartProcessorService = $cartProcessorService;
        $this->configService = $configService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        $events = [];
        $events[CartConvertedEvent::class] = 'onCartConvertedEvent';
        if (class_exists('\Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent')) {
            // for newer shopware versions
            $events[BeforeLineItemAddedEvent::class] = 'onCartUpdatedEvent';
            $events[BeforeLineItemQuantityChangedEvent::class] = 'onCartUpdatedEvent';
            $events[BeforeLineItemRemovedEvent::class] = 'onCartUpdatedEvent';
        } else if (class_exists('\Shopware\Core\Checkout\Cart\Event\LineItemAddedEvent')) {
            // for older shopware versions
            $events[LineItemAddedEvent::class] = 'onCartUpdatedEvent';
            $events[LineItemQuantityChangedEvent::class] = 'onCartUpdatedEvent';
            $events[LineItemRemovedEvent::class] = 'onCartUpdatedEvent';
        }
        return $events;
    }

    /**
     * @param CartConvertedEvent $event
     */
    public function onCartConvertedEvent(CartConvertedEvent $event): void
    {
        $this->configService->setSalesChannelId($event->getSalesChannelContext()->getSalesChannelId());
        if ($this->configService->isAbandonedCartTrackingEnabled()) {
            $this->cartEventProducer->processOrder(
                $event->getOriginalConvertedCart(),
                $event->getCart(),
                $event->getSalesChannelContext()
            );
        }
    }

    /**
     * @param $event
     */
    public function onCartUpdatedEvent($event): void
    {
        if (method_exists($event, 'getSalesChannelContext') && $event->getSalesChannelContext() instanceof SalesChannelContext) {
            $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        } else if (method_exists($event, 'getContext') && $event->getContext() instanceof SalesChannelContext) {
            $salesChannelId = $event->getContext()->getSalesChannelId();
        } else {
            return;
        }
        $this->configService->setSalesChannelId($salesChannelId);
        if ($this->configService->isAbandonedCartTrackingEnabled()) {
            $this->cartProcessorService->setShouldCollectData(true);
        }
    }
}
