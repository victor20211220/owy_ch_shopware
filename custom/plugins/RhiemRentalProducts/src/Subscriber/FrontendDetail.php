<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Subscriber;

use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Rhiem\RhiemRentalProducts\Components\Cart\CartHelper;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FrontendDetail implements EventSubscriberInterface
{
    private readonly CartService $cartService;

    private readonly CartHelper $cartHelper;

    private readonly SystemConfigService $systemConfigService;

    public function __construct(
        CartService $cartService,
        CartHelper $cartHelper,
        SystemConfigService $systemConfigService
    ) {
        $this->cartService = $cartService;
        $this->cartHelper = $cartHelper;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageCriteriaEvent::class => 'onProductCriteriaCreation',
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductCriteriaCreation(ProductPageCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('rentalProduct');
        $criteria->addAssociation('rentalProduct.parent');
        $criteria->addAssociation('rentalProduct.children');
        $criteria->addAssociation('rentalProduct.prices');
        $criteria->addAssociation('rentalProduct.product');
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        if(!$this->systemConfigService->get('RhiemRentalProducts.config.uniformRentalPeriods')) return;

        $page = $event->getPage();
        $context = $event->getSalesChannelContext();
        $rentalTime = $this->cartHelper->getCurrentRentalTime($this->cartService->getCart($context->getToken(), $context));

        if(!$rentalTime instanceof RentalTime) return;

        $page->addArrayExtension('currentRentalTime', ['period' => $rentalTime]);
    }
}
