<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreLocatorDetail;

use Acris\StoreLocator\Components\StoreLocatorService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class StoreLocatorDetailPageLoader
{
    private EventDispatcherInterface $eventDispatcher;
    private GenericPageLoaderInterface $genericPageLoader;
    private StoreLocatorService $storeLocatorService;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        GenericPageLoaderInterface $genericPageLoader,
        StoreLocatorService $storeLocatorService
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->genericPageLoader = $genericPageLoader;
        $this->storeLocatorService = $storeLocatorService;
    }

    public function load(string $storeId, Request $request, SalesChannelContext $salesChannelContext): StoreLocatorDetailPage
    {
        $page = $this->genericPageLoader->load($request, $salesChannelContext);
        $page = StoreLocatorDetailPage::createFrom($page);
        $store = $this->storeLocatorService->getStoreById($storeId, $salesChannelContext, $request);

        $page->setStore($store);

        $this->eventDispatcher->dispatch(
            new StoreLocatorDetailPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }
}
