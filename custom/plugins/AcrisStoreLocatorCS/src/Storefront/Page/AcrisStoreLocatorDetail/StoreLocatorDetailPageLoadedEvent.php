<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreLocatorDetail;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class StoreLocatorDetailPageLoadedEvent extends PageLoadedEvent
{
    protected StoreLocatorDetailPage $page;

    public function __construct(StoreLocatorDetailPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): StoreLocatorDetailPage
    {
        return $this->page;
    }
}
