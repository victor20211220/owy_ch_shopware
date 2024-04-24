<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreLocator;

use Acris\StoreLocator\Components\StoreLocatorService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class StoreLocatorSelectionPageLoader
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

    public function load(Request $request, SalesChannelContext $salesChannelContext): StoreLocatorSelectionPage
    {
        $page = $this->genericPageLoader->load($request, $salesChannelContext);
        $page = StoreLocatorSelectionPage::createFrom($page);

        $page->setStores($this->storeLocatorService->getActiveStores($salesChannelContext->getContext()));
        $this->eventDispatcher->dispatch(
            new StoreLocatorSelectionLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }
}
