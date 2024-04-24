<?php declare(strict_types=1);
namespace NetzpSearchAdvanced6\Subscriber;

use NetzpSearchAdvanced6\Components\SearchInterface;
use NetzpSearchAdvanced6\Core\Content\SearchLog\SearchLogDefinition;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SearchInterface $searchService,
                                private readonly SystemConfigService $systemConfigService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SearchPageLoadedEvent::class        => 'doSearch',
            SuggestPageLoadedEvent::class       => 'doSearchSuggest',
            RequestEvent::class                 => 'onKernelRequest',
            ProductListingCriteriaEvent::class  => 'loadProductListingCriteria',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $salesChannelId = $event->getRequest()->attributes->get('sw-sales-channel-id') ?? null;
        $pluginConfig = $this->systemConfigService->get('NetzpSearchAdvanced6.config', $salesChannelId);
        $useSynonyms = (bool)$pluginConfig['useSynonyms'];

        $request = $event->getRequest();
        if(! $useSynonyms || ! $request->query->has('search')) {
            return;
        }

        $query = $this->searchService->replaceSynonyms($request->query->get('search'));
        $request->query->set('search', $query);
    }

    public function doSearch(PageLoadedEvent $event)
    {
        $pluginConfig = $this->systemConfigService->get(
            'NetzpSearchAdvanced6.config',
            $event->getSalesChannelContext()->getSalesChannelId() ?? null
        );
        $logQueries = array_key_exists('logQueries', $pluginConfig) ? $pluginConfig['logQueries'] : 0;

        $query = $event->getPage()->getSearchTerm();
        $searchResults = $this->searchService->doSearch($query, $event->getSalesChannelContext());

        if($logQueries == 1 || $logQueries == 2)
        {
            $this->searchService->logQuery($query, $event, $searchResults, SearchLogDefinition::ORIGIN_SEARCH);
        }

        $firstTabWithResults = '';
        if($event->getPage()->getListing()->getTotal() == 0)
        {
            foreach($searchResults as $searchCategory => $searchCategoryResults)
            {
                if(($searchCategoryResults['total'] ?? 0) > 0)
                {
                    $firstTabWithResults = $searchCategory;
                    break;
                }
            }
        }

        if($firstTabWithResults != '')
        {
            $event->getRequest()->attributes->set('tab', $firstTabWithResults);
        }

        $event->getPage()->addExtension('netzpSearch', new ArrayStruct($searchResults));
    }

    public function doSearchSuggest(SuggestPageLoadedEvent $event)
    {
        $pluginConfig = $this->systemConfigService->get(
            'NetzpSearchAdvanced6.config',
            $event->getSalesChannelContext()->getSalesChannelId() ?? null
        );
        $logQueries = array_key_exists('logQueries', $pluginConfig) ? $pluginConfig['logQueries'] : 0;

        $query = $event->getPage()->getSearchTerm();
        $searchResults = $this->searchService->doSearch($query, $event->getSalesChannelContext(), true);

        if($logQueries == 2) {
            $this->searchService->logQuery($query, $event, $searchResults, SearchLogDefinition::ORIGIN_AJAX);
        }

        $firstTabWithResults = '';
        if($event->getPage()->getSearchResult()->getTotal() == 0)
        {
            foreach($searchResults as $searchCategory => $searchCategoryResults)
            {
                if(($searchCategoryResults['total'] ?? 0) > 0)
                {
                    $firstTabWithResults = $searchCategory;
                    break;
                }
            }
        }

        if($firstTabWithResults != '')
        {
            $event->getRequest()->attributes->set('tab', $firstTabWithResults);
        }

        $event->getPage()->addExtension('netzpSearch', new ArrayStruct($searchResults));
    }

    public function loadProductListingCriteria(ProductListingCriteriaEvent $event): void
    {
        $pluginConfig = $this->systemConfigService->get(
            'NetzpSearchAdvanced6.config',
            $event->getSalesChannelContext()->getSalesChannelId() ?? null
        );
        $manufacturerCmsPage = $pluginConfig['searchManufacturerCmsPage'] ?? '';

        if($manufacturerCmsPage != "" &&
            $event->getRequest()->attributes->get('navigationId', '') == $manufacturerCmsPage)
        {
            $event->getCriteria()->resetFilters();
            $event->getCriteria()->addFilter(new ProductAvailableFilter($event->getSalesChannelContext()->getSalesChannel()->getId()));
        }
    }
}
