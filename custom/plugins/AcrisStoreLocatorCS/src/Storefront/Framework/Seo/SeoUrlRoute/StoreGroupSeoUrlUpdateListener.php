<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Framework\Seo\SeoUrlRoute;

use Acris\Look\Core\Content\Look\DataAbstractionLayer\LookIndexerEvent;
use Acris\StoreLocator\Core\Content\StoreLocator\DataAbstractionLayer\StoreGroupIndexerEvent;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Seo\SeoUrlUpdater;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StoreGroupSeoUrlUpdateListener implements EventSubscriberInterface
{
    private SeoUrlUpdater $seoUrlUpdater;

    private Connection $connection;

    private EntityIndexerRegistry $indexerRegistry;

    public function __construct(SeoUrlUpdater $seoUrlUpdater, Connection $connection, EntityIndexerRegistry $indexerRegistry)
    {
        $this->seoUrlUpdater = $seoUrlUpdater;
        $this->connection = $connection;
        $this->indexerRegistry = $indexerRegistry;
    }

    public function detectSalesChannelEntryPoints(EntityWrittenContainerEvent $event): void
    {
        $properties = ['navigationCategoryId', 'footerCategoryId', 'serviceCategoryId'];

        $salesChannelIds = $event->getPrimaryKeysWithPropertyChange(SalesChannelDefinition::ENTITY_NAME, $properties);

        if (empty($salesChannelIds)) {
            return;
        }

        $this->indexerRegistry->sendIndexingMessage(['acris_store_group.indexer']);
    }

    public static function getSubscribedEvents()
    {
        return [
            StoreGroupIndexerEvent::class => 'updateStoreGroupUrls',
            EntityWrittenContainerEvent::class => 'detectSalesChannelEntryPoints',
        ];
    }

    public function updateStoreGroupUrls(StoreGroupIndexerEvent $event): void
    {
        $ids = $event->getIds();

        $this->seoUrlUpdater->update(StoreGroupPageSeoUrlRoute::ROUTE_NAME, $ids);
    }
}
