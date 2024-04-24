<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Framework\Seo\SeoUrlRoute;

use Acris\StoreLocator\Custom\StoreGroupDefinition;
use Acris\StoreLocator\Custom\StoreGroupEntity;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlMapping;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class StoreGroupPageSeoUrlRoute implements SeoUrlRouteInterface
{
    public const ROUTE_NAME = 'frontend.storeLocator.storeGroup';
    public const DEFAULT_TEMPLATE = 'store-locator/{% if group.translated.seoUrl %}{{ group.translated.seoUrl }}{% else %}{{ group.translated.name }}{% endif %}';

    private StoreGroupDefinition $storeGroupDefinition;

    public function __construct(StoreGroupDefinition $storeGroupDefinition)
    {
        $this->storeGroupDefinition = $storeGroupDefinition;
    }

    public function getConfig(): SeoUrlRouteConfig
    {
        return new SeoUrlRouteConfig(
            $this->storeGroupDefinition,
            self::ROUTE_NAME,
            self::DEFAULT_TEMPLATE,
            true
        );
    }

    public function prepareCriteria(Criteria $criteria, SalesChannelEntity $salesChannel): void
    {
        $criteria->addAssociation('media');
        $criteria->addAssociation('acrisStores');
    }

    public function getMapping(Entity $group, ?SalesChannelEntity $salesChannel): SeoUrlMapping
    {
        if (!$group instanceof StoreGroupEntity) {
            throw new \InvalidArgumentException('Expected StoreGroupEntity');
        }

        $groupJson = $group->jsonSerialize();

        return new SeoUrlMapping(
            $group,
            ['groupId' => $group->getId()],
            [
                'group' => $groupJson,
            ]
        );
    }
}
