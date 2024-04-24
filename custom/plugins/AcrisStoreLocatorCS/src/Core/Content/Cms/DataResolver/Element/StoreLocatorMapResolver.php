<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\Cms\DataResolver\Element;

use Acris\StoreLocator\Components\StoreLocatorGateway;
use Acris\StoreLocator\Core\Content\Cms\SalesChannel\Struct\StoreMapStruct;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class StoreLocatorMapResolver extends AbstractCmsElementResolver
{
    private StoreLocatorGateway $storeLocatorGateway;
    private EntityRepository $mediaRepository;
    private SystemConfigService $systemConfigService;

    public function __construct(
        EntityRepository $mediaRepository,
        SystemConfigService $systemConfigService,
        StoreLocatorGateway $storeLocatorGateway
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->systemConfigService = $systemConfigService;
        $this->storeLocatorGateway = $storeLocatorGateway;
    }

    public function getType(): string
    {
        return 'acris-store-google-map';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $mediaStoreIconId = $this->systemConfigService->get('AcrisStoreLocatorCS.config.windowStoreIcon', $resolverContext->getSalesChannelContext()->getSalesChannel()->getId());
        $mediaHomeIconId = $this->systemConfigService->get('AcrisStoreLocatorCS.config.windowHomeIcon', $resolverContext->getSalesChannelContext()->getSalesChannel()->getId());
        $mediaStoreIcon = null;
        $mediaHomeIcon = null;
        $store = new StoreMapStruct(null, null, null);

        if ($mediaStoreIconId) {
            $mediaStoreIcon = $this->mediaRepository->search((new Criteria())->addFilter(new EqualsAnyFilter('id', [$mediaStoreIconId])), $resolverContext->getSalesChannelContext()->getContext())->getEntities()->getElements();
            $store->setMediaStore($mediaStoreIcon);
        }

        if ($mediaHomeIconId) {
            $mediaHomeIcon = $this->mediaRepository->search((new Criteria())->addFilter(new EqualsAnyFilter('id', [$mediaHomeIconId])), $resolverContext->getSalesChannelContext()->getContext())->getEntities()->getElements();
            $store->setMediaHome($mediaHomeIcon);
        }
        if (!empty($slot) && !empty($slot->getFieldConfig()) && $slot->getFieldConfig()->has('store') && !empty($slot->getFieldConfig()->get('store')->getValue())) {
            $storeId = $slot->getFieldConfig()->get('store')->getValue();
            $storeEntity = $this->storeLocatorGateway->loadStoreById($storeId, $resolverContext->getSalesChannelContext());

            $store->setStore($storeEntity);
        }

        $slot->setData($store);
    }

}
