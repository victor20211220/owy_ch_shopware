<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreLocator;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class StoreLocatorSelectionLoadedEvent extends PageLoadedEvent
{
    protected StoreLocatorSelectionPage $page;

    public function __construct(StoreLocatorSelectionPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): StoreLocatorSelectionPage
    {
        return $this->page;
    }
}
