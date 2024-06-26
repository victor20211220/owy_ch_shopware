<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreGroup;

use Acris\StoreLocator\Components\StoreLocatorService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Snippet\SnippetService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class StoreGroupPageLoader
{
    private EntityRepository $mediaRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SystemConfigService $systemConfigService;
    private GenericPageLoaderInterface $genericPageLoader;
    private SnippetService $snippetService;
    private StoreLocatorService $storeLocatorService;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityRepository $mediaRepository,
        SystemConfigService $systemConfigService,
        GenericPageLoaderInterface $genericPageLoader,
        SnippetService $snippetService,
        StoreLocatorService $storeLocatorService
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->mediaRepository = $mediaRepository;
        $this->systemConfigService = $systemConfigService;
        $this->genericPageLoader = $genericPageLoader;
        $this->snippetService = $snippetService;
        $this->storeLocatorService = $storeLocatorService;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return StoreGroupPage
     */
    public function load(string $groupId, Request $request, SalesChannelContext $salesChannelContext): StoreGroupPage
    {
        $page = $this->genericPageLoader->load($request, $salesChannelContext);
        $page = StoreGroupPage::createFrom($page);
        $snippet = 'acrisStoreLocator.metatitle';
        $mediaStoreIconId = $this->systemConfigService->get('AcrisStoreLocatorCS.config.windowStoreIcon', $salesChannelContext->getSalesChannel()->getId());
        $mediaHomeIconId = $this->systemConfigService->get('AcrisStoreLocatorCS.config.windowHomeIcon', $salesChannelContext->getSalesChannel()->getId());

        $snippetsResult = $this->snippetService->getList(1, 1000, $salesChannelContext->getContext(), ['translationKey' => [$snippet]], []);
        $snippetSet = $this->snippetService->getSnippetSet($salesChannelContext->getSalesChannel()->getId(), $salesChannelContext->getContext()->getLanguageId(), '', $salesChannelContext->getContext());

        foreach ($snippetsResult['data'][$snippet] as $snippetData) {
            if ($snippetData['setId'] === $snippetSet->getId()) {
                $page->getMetaInformation()->setMetaTitle($snippetData['value']);
            }
        }

        if ($mediaStoreIconId) {
            $mediaStoreIcon = $this->mediaRepository->search((new Criteria())->addFilter(new EqualsAnyFilter('id', [$mediaStoreIconId])), $salesChannelContext->getContext())->getEntities()->getElements();
            $page->setMediaStore($mediaStoreIcon);
        }

        if ($mediaHomeIconId) {
            $mediaHomeIcon = $this->mediaRepository->search((new Criteria())->addFilter(new EqualsAnyFilter('id', [$mediaHomeIconId])), $salesChannelContext->getContext())->getEntities()->getElements();
            $page->setMediaHome($mediaHomeIcon);
        }

        $page->setGroupId($groupId);
        $page->setGroup($this->storeLocatorService->getStoreGroupWithId($groupId, $salesChannelContext));

        $stores = $this->storeLocatorService->loadStoresForCurrentGroup($groupId, $salesChannelContext);
        $page->setStores($stores);

        $page->setHasCover($this->storeLocatorService->checkStoresHasCover($stores));

        $this->eventDispatcher->dispatch(
            new StoreGroupLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }
}
