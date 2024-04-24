<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreGroup;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class StoreGroupLoadedEvent extends PageLoadedEvent
{
    /**
     * @var StoreGroupPage
     */
    protected $page;

    public function __construct(StoreGroupPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): StoreGroupPage
    {
        return $this->page;
    }
}
