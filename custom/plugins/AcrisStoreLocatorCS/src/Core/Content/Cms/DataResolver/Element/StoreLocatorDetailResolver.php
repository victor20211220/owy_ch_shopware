<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\Cms\DataResolver\Element;

use Acris\StoreLocator\Components\StoreLocatorGateway;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;

class StoreLocatorDetailResolver extends AbstractCmsElementResolver
{
    private StoreLocatorGateway $storeLocatorGateway;

    public function __construct(
        StoreLocatorGateway $storeLocatorGateway
    ) {
        $this->storeLocatorGateway = $storeLocatorGateway;
    }

    public function getType(): string
    {
        return 'acris-store-details';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        if (!empty($slot) && !empty($slot->getFieldConfig()) && $slot->getFieldConfig()->has('store') && !empty($slot->getFieldConfig()->get('store')->getValue())) {
            $storeId = $slot->getFieldConfig()->get('store')->getValue();
            $store = $this->storeLocatorGateway->loadStoreByIdForElement($storeId, $resolverContext->getSalesChannelContext());
            $slot->setData($store);
        }
    }

}
