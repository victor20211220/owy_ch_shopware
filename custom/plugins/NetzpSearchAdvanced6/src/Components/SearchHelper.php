<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Components;

use Doctrine\DBAL\Connection;
use NetzpSearchAdvanced6\Core\Content\SearchLog\SearchLogDefinition;
use NetzpSearchAdvanced6\Core\SearchResult;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SearchHelper implements SearchInterface
{
    final public const TYPE_CATEGORY     =  1;
    final public const TYPE_MANUFACTURER =  2;
    final public const TYPE_CMS          =  3;

    final public const SUGGEST_LIMIT     = 10;

    public function __construct(private readonly SystemConfigService $config,
                                private readonly EventDispatcherInterface $eventDispatcher,
                                private readonly Connection $connection,
                                private readonly EntityRepository $categoryRepository,
                                private readonly EntityRepository $cmsPageRepository,
                                private readonly EntityRepository $productManufacturerRepository,
                                private readonly EntityRepository $searchSynonymsRepository,
                                private readonly EntityRepository $searchLogRepository,
    )
    {
    }

    public function doSearch(string $query, SalesChannelContext $salesChannelContext, bool $isSuggest = false): array
    {
        $pluginConfig = $this->config->get(
            'NetzpSearchAdvanced6.config',
            $salesChannelContext->getSalesChannel()->getId()
        );
        $searchCategories = (bool)$pluginConfig['searchCategories'];
        $searchManufacturer = (bool)$pluginConfig['searchManufacturer'];
        $searchCms = (bool)$pluginConfig['searchCms'];
        $searchCmsAdditionalConfig = $this->config->get(
            'NetzpSearchAdvanced6.config.searchCmsConfig',
            $salesChannelContext->getSalesChannel()->getId()
        ) ?? '';

        $externalSearchProvider = [];
        if ($listeners = $this->eventDispatcher->getListeners(RegisterSearchProviderEvent::EVENT_NAME)) {
            foreach ($listeners as $listener) {
                $registerEvent = new RegisterSearchProviderEvent($salesChannelContext);
                if ($registerEvent->isPropagationStopped())
                {
                    break;
                }

                call_user_func($listener, $registerEvent, RegisterSearchProviderEvent::EVENT_NAME, $this);

                if($registerEvent->getData()) {
                    $externalSearchProvider[] = $registerEvent;
                }
            }
        }

        $results = [];
        if($searchCategories)
        {
            $results['categories'] = [
                'label' => 'netzp.search.categories',
                'data'  => $this->searchCategories($query, $salesChannelContext, $isSuggest)
            ];
        }
        if($searchManufacturer)
        {
            $results['manufacturer'] = [
                'label' => 'netzp.search.manufacturer',
                'data'  => $this->searchManufacturers($query, $salesChannelContext, $isSuggest)
            ];
        }
        if($searchCms)
        {
            $results['cms'] = [
                'label' => 'netzp.search.cms',
                'data'  => $this->searchCms($query, $salesChannelContext, $isSuggest, $searchCmsAdditionalConfig)
            ];
        }

        foreach($externalSearchProvider as $searchProvider)
        {
            $data = $searchProvider->getData();
            $results[$data['key']] = [
                'label' => $data['label'],
                'data'  => call_user_func($data['function'], $query, $salesChannelContext, $isSuggest)
            ];
        }

        foreach($results as &$result)
        {
            $result['total'] = (is_countable($result['data']) ? count($result['data']) : 0) > 0 ?
                                    $result['data'][0]->getTotal() :
                                    0;
        }

        return $results;
    }

    private function searchCategories($query, SalesChannelContext $salesChannelContext, bool $isSuggest = false)
    {
        $results = [];
        $categories = $this->getCategories($query, $salesChannelContext, $isSuggest);

        foreach ($categories->getEntities() as $category)
        {
            $tmpResult = new SearchResult();
            $tmpResult->setType(static::TYPE_CATEGORY);
            $tmpResult->setId($category->getId());
            $tmpResult->setTitle($category->getTranslated()['name'] ?? '');
            $tmpResult->setDescription($category->getTranslated()['description'] ?? '');
            $tmpResult->setBreadcrumb($category->getTranslated()['breadcrumb'] ?? []);
            if($category->getType() == CategoryDefinition::TYPE_LINK)
            {
                $tmpResult->addExtension('link', new ArrayStruct(['value' => $category->getTranslated()['externalLink']]));
            }
            $tmpResult->setMedia($category->getMedia());
            $tmpResult->setTotal($categories->getTotal());
            $results[] = $tmpResult;
        }

        return $results;
    }

    private function getCategories($query, SalesChannelContext $salesChannelContext, bool $isSuggest = false)
    {
        $keywords = $this->explodeSearchQuery($query);

        if (count($keywords) > 0)
        {
            $criteria = new Criteria();
            if($isSuggest) {
                $criteria->setLimit(static::SUGGEST_LIMIT);
                $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
            }
            $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));

            $criteria->addAssociation('media');
            $criteria->addFilter(new EqualsFilter('active', true));
            $criteria->addFilter(new ContainsFilter('path', '|' . $salesChannelContext->getSalesChannel()->getNavigationCategoryId() . '|'));
            $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
                new EqualsFilter('type', CategoryDefinition::TYPE_FOLDER)
            ]));

            $filter = [];
            foreach ($keywords as $keyword)
            {
                $filter[] = new ContainsFilter('name', $keyword);
                $filter[] = new ContainsFilter('description', $keyword);
            }
            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $filter));

            return $this->categoryRepository->search($criteria, $salesChannelContext->getContext());
        }

        return null;
    }

    private function searchCms(string $query,
                               SalesChannelContext $salesChannelContext,
                               bool $isSuggest = false,
                               string $additionalConfig = '')
    {
        $results = [];
        $cmsPages = $this->getCmsPages($query, $salesChannelContext, $isSuggest, $additionalConfig);

        foreach ($cmsPages->getEntities() as $cmsPage)
        {
            $tmpResult = new SearchResult();
            $tmpResult->setType(static::TYPE_CMS);
            $tmpResult->setId($cmsPage->getId());

            $category = $this->getCmsCategory(
                $cmsPage,
                $salesChannelContext->getSalesChannel()->getNavigationCategoryId(),
                $salesChannelContext->getSalesChannel()->getFooterCategoryId(),
                $salesChannelContext->getSalesChannel()->getServiceCategoryId()
            );

            if($category)
            {
                $name = $category->getTranslated()['name'] ?? '';
                $description = $category->getTranslated()['description'] ?? '';
                $tmpResult->addExtension('categoryId', new ArrayStruct(['value' => $category->getId()]));

                $tmpResult->setTitle($name != '' ? $name : $cmsPage->getTranslated()['name'] ?? '');
                $tmpResult->setDescription($description);
                $tmpResult->setMedia($category->getMedia());

                $tmpResult->setTotal($cmsPages->getTotal());

                $results[] = $tmpResult;
            }
        }

        return $results;
    }

    private function getCmsCategory($cmsPage,
                                    $navigationCategoryId,
                                    $footerCategoryId,
                                    $serviceCategoryId)
    {
        foreach($cmsPage->getCategories() as $category)
        {
            if($category->getActive() &&
                $category->getPath() &&
                (
                    str_contains((string) $category->getPath(), '|' . $navigationCategoryId . '|') ||
                    str_contains((string) $category->getPath(), '|' . $footerCategoryId . '|') ||
                    str_contains((string) $category->getPath(), '|' . $serviceCategoryId . '|')
                )
            )
            {
                return $category;
            }
        }

        return null;
    }

    private function getCmsPages(string $query,
                                 SalesChannelContext $salesChannelContext,
                                 bool $isSuggest = false,
                                 string $additionalConfig = '')
    {
        $keywords = $this->explodeSearchQuery($query);

        $searchConfigFields = array_filter(array_merge([
            'content',
            'contents',
            'text',
            'title'
        ], explode(',', $additionalConfig)));

        if (count($keywords) > 0)
        {
            $criteria = new Criteria();
            if($isSuggest) {
                $criteria->setLimit(static::SUGGEST_LIMIT);
                $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
            }
            $criteria->addAssociation('categories');
            $criteria->addAssociation('categories.media');
            $criteria->addAssociation('sections.blocks.slots');
            $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));

            $filter = [];
            foreach ($keywords as $keyword)
            {
                $filter[] = new ContainsFilter('name', $keyword);
                foreach($searchConfigFields as $searchConfigField)
                {
                    $filter[] = new ContainsFilter('sections.blocks.slots.config.' . trim($searchConfigField), $keyword);
                }
            }

            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR,
                $filter
            ));

            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                new ContainsFilter(
                    'categories.path',
                    '|' . $salesChannelContext->getSalesChannel()->getNavigationCategoryId() . '|'
                ),
                new ContainsFilter(
                    'categories.path',
                    '|' . $salesChannelContext->getSalesChannel()->getFooterCategoryId() . '|'
                ),
                new ContainsFilter(
                    'categories.path',
                    '|' . $salesChannelContext->getSalesChannel()->getServiceCategoryId() . '|'
                )
            ]));
            $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
                new EqualsFilter('categories.id', null)
            ]));

            return $this->cmsPageRepository->search($criteria, $salesChannelContext->getContext());
        }

        return null;
    }

    private function searchManufacturers(string $query,
                                         SalesChannelContext $salesChannelContext,
                                         bool $isSuggest = false)
    {
        $results = [];
        $manufacturers = $this->getManufacturers($query, $salesChannelContext, $isSuggest);

        foreach ($manufacturers->getEntities() as $manufacturer)
        {
            $tmpResult = new SearchResult();
            $tmpResult->setType(static::TYPE_MANUFACTURER);
            $tmpResult->setId($manufacturer->getId());
            $tmpResult->setTitle($manufacturer->getTranslated()['name'] ?? '');
            $tmpResult->setDescription($manufacturer->getTranslated()['description'] ?? '');
            $tmpResult->setMedia($manufacturer->getMedia());
            $tmpResult->setTotal($manufacturers->getTotal());
            $tmpResult->addExtension('link', new ArrayStruct([
                'value' => $manufacturer->getLink() ?? '']
            ));
            $tmpResult->addExtension('products', new ArrayStruct([
                'value' => $this->getProductCountForManufacturer($manufacturer->getId())
            ]));

            $results[] = $tmpResult;
        }
        return $results;
    }

    private function getProductCountForManufacturer(string $manufacturerId): int
    {
        $sql = 'SELECT count(id) 
                  FROM product 
                 WHERE product_manufacturer_id = UNHEX(:manufacturerId) AND 
                       product_manufacturer_version_id = UNHEX(:versionId)';

        $statement = $this->connection->prepare($sql);
        $productCount = $statement->executeQuery([
            'manufacturerId' => $manufacturerId,
            'versionId'      => Defaults::LIVE_VERSION
        ])->fetchOne();

        return (int)$productCount;
    }

    private function getManufacturers(string $query,
                                      SalesChannelContext $salesChannelContext,
                                      bool $isSuggest = false)
    {
        $keywords = $this->explodeSearchQuery($query);

        if (count($keywords) > 0)
        {
            $criteria = new Criteria();
            if($isSuggest) {
                $criteria->setLimit(static::SUGGEST_LIMIT);
                $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
            }
            $criteria->addAssociation('media');
            $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));

            $filter = [];
            foreach ($keywords as $keyword) {
                $filter[] = new ContainsFilter('name', $keyword);
                $filter[] = new ContainsFilter('description', $keyword);
            }
            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $filter));

            return $this->productManufacturerRepository->search($criteria, $salesChannelContext->getContext());
        }

        return null;
    }

    private function explodeSearchQuery(string $query): array
    {
        $query = trim((string)$query);
        if(str_starts_with($query, '"') && str_ends_with($query, '"'))
        {
            return [ trim($query, '"') ];
        }

        return explode(' ', $query);
    }

    public function replaceSynonyms(string $query): string
    {
        $newQuery = $query;
        $keywords = $this->explodeSearchQuery($query);

        if (count($keywords) > 0)
        {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsAnyFilter('synonym', $keywords));

            $synonyms = $this->searchSynonymsRepository->search($criteria, new Context(new SystemSource()))->getEntities();
            foreach($synonyms as $synonym) {
                $newQuery = str_replace($synonym->getSynonym(), $synonym->getReplace(), $newQuery);
            }
            // TODO later - replace REGEX synonyms
        }

        return $newQuery;
    }

    public function logQuery(string $query, PageLoadedEvent $event, array $searchResults, int $origin)
    {
        $salesChannelId = $event->getContext()->getSource()->getSalesChannelId();
        $languageId = $event->getContext()->getLanguageId();

        if($origin == SearchLogDefinition::ORIGIN_AJAX) {
            $hits = $event->getPage()->getSearchResult()->getTotal();
        }
        else {
            $hits = $event->getPage()->getListing()->getTotal();
        }

        $additionalHits = [];
        foreach($searchResults as $searchResult)
        {
            if($searchResult['total'] > 0) {
                $additionalHits[] = [
                    'name' => $searchResult['label'],
                    'hits' => $searchResult['total']
                ];
            }
        }

        $this->searchLogRepository->create([
            [
                'query'          => $query,
                'hits'           => $hits,
                'additionalHits' => $additionalHits,
                'origin'         => $origin,
                'salesChannelId' => $salesChannelId,
                'languageId'     => $languageId
            ],
        ], $event->getContext());
    }
}
