<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\Cms\DataResolver\Element;

use Acris\StoreLocator\Components\StoreLocatorService;
use Acris\StoreLocator\Core\Content\Cms\SalesChannel\Struct\StoreGroupListing;
use Acris\StoreLocator\Core\Content\Cms\SalesChannel\Struct\StoreHasCover;
use Acris\StoreLocator\Custom\StoreGroupEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StoreLocatorGroupResolver extends AbstractCmsElementResolver
{
    const DEFAULT_EXTENSION_STORES = 'stores';
    const DEFAULT_EXTENSION_HAS_COVER = 'storesHasCover';
    const DEFAULT_EXTENSION_DISPLAY_TYPE = 'displayType';
    const DEFAULT_DISPLAY_TYPE_OPTION_LISTING = 'listing';

    private ContainerInterface $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    public function getType(): string
    {
        return 'acris-store-group';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        if (empty($slot) || empty($slot->getFieldConfig()) || !$slot->getFieldConfig()->has('group') || empty($slot->getFieldConfig()->get('group')) || empty($slot->getFieldConfig()->get('group')->getValue())) {
            return;
        }

        $listing = !empty($slot->getFieldConfig()) && $slot->getFieldConfig()->has('displayType') && !empty($slot->getFieldConfig()->get('displayType')) && !empty($slot->getFieldConfig()->get('displayType')->getValue()) ? $slot->getFieldConfig()->get('displayType')->getValue() : null;

        $groupId = $slot->getFieldConfig()->get('group')->getValue();

        /** @var StoreLocatorService $storeLocatorService */
        $storeLocatorService = $this->container->get(StoreLocatorService::class);

        if (empty($storeLocatorService)) return;

        $group = $storeLocatorService->loadStoreGroupById($groupId, $resolverContext->getSalesChannelContext());
        if (empty($group)) return;

        $stores = $storeLocatorService->loadStoresForCurrentGroup($groupId, $resolverContext->getSalesChannelContext());
        $group->addExtension(self::DEFAULT_EXTENSION_STORES, $stores);

        $this->checkGroupListing($group, $listing);

        $hasCover = $storeLocatorService->checkStoresHasCover($stores);
        if ($hasCover === true) {
            $group->addExtension(self::DEFAULT_EXTENSION_HAS_COVER, new StoreHasCover());
        }

        $slot->setData($group);
    }

    private function checkGroupListing(StoreGroupEntity $group, ?string $listing): void
    {
        if ($listing === self::DEFAULT_DISPLAY_TYPE_OPTION_LISTING) {
            $group->addExtension(self::DEFAULT_EXTENSION_DISPLAY_TYPE, new StoreGroupListing());
        } else {
            if ($group->hasExtension(self::DEFAULT_EXTENSION_DISPLAY_TYPE)) {
                $group->removeExtension(self::DEFAULT_EXTENSION_DISPLAY_TYPE);
            }
        }
    }
}
