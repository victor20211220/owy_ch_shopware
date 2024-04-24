<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreLocator;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class StoreLocatorLoadedEvent extends PageLoadedEvent
{
    /**
     * @var StoreLocatorPage
     */
    protected $page;

    public function __construct(StoreLocatorPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): StoreLocatorPage
    {
        return $this->page;
    }
}
