<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Framework\Seo\SeoUrlRoute;

use Acris\StoreLocator\Custom\StoreLocatorDefinition;
use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlMapping;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class StorePageSeoUrlRoute implements SeoUrlRouteInterface
{
    public const ROUTE_NAME = 'frontend.storeLocator.detail';
    public const DEFAULT_TEMPLATE = 'store-locator/{% if store.translated.seoUrl %}{{ store.translated.seoUrl }}{% else %}{{ store.translated.name }}{% endif %}';

    private StoreLocatorDefinition $storeDefinition;

    public function __construct(StoreLocatorDefinition $storeDefinition)
    {
        $this->storeDefinition = $storeDefinition;
    }

    public function getConfig(): SeoUrlRouteConfig
    {
        return new SeoUrlRouteConfig(
            $this->storeDefinition,
            self::ROUTE_NAME,
            self::DEFAULT_TEMPLATE,
            true
        );
    }

    public function prepareCriteria(Criteria $criteria, SalesChannelEntity $salesChannel): void
    {
        $criteria->addAssociation('country');
        $criteria->addAssociation('cmsPage');
        $criteria->addAssociation('storeGroup');
        $criteria->addAssociation('acrisOrderDeliveryStore');
    }

    public function getMapping(Entity $store, ?SalesChannelEntity $salesChannel): SeoUrlMapping
    {
        if (!$store instanceof StoreLocatorEntity) {
            throw new \InvalidArgumentException('Expected StoreLocatorEntity');
        }

        $storeJson = $store->jsonSerialize();

        return new SeoUrlMapping(
            $store,
            ['storeId' => $store->getId()],
            [
                'store' => $storeJson,
            ]
        );
    }
}
