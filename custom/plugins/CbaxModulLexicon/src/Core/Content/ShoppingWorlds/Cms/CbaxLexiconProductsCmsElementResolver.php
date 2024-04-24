<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\ShoppingWorlds\Cms;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;

use Cbax\ModulLexicon\Core\Content\Bundle\LexiconEntryEntity;

class CbaxLexiconProductsCmsElementResolver extends AbstractCmsElementResolver
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SalesChannelRepository $salesChannelProductRepository,
        private readonly ProductStreamBuilder $productStreamBuilder,
        private readonly Connection $connection
    )
    {

    }

    public function getType(): string
    {
        return 'cbax-lexicon-products';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $data = new LexiconStruct();

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $config = $slot->getTranslated()['config'] ?? [];
        if (empty($slot->getFieldConfig())) {
            $slot->setFieldConfig(new FieldConfigCollection($config));
        }

        if ($resolverContext instanceof EntityResolverContext) {
            $entity = $resolverContext->getEntity();

            if (!empty($entity)) {
                $data->setEntry($entity);
                $data->setSalesChannelProducts($this->getProducts($salesChannelContext->getContext(), $entity, $salesChannelContext));
            }
        }

        $slot->setData($data);
    }

    private function getProducts(Context $context, LexiconEntryEntity $entry, SalesChannelContext $salesChannelContext): ?array
    {
        $productCriteria = new Criteria();
        $productCriteria->addAssociation('cover');
        $productCriteria->addAssociation('prices');
        $productCriteria->addAssociation('unit');
        $productCriteria->addAssociation('deliveryTime');

        if ($entry->get('listingType') == 'selected_article') {
            $ids = $entry->products->getIds();

            // Filtern nach ermittelten IDs
            $productCriteria->addFilter(new EqualsAnyFilter('id', $ids));

        } elseif (($entry->get('listingType') == 'product_stream') && !empty($entry->getProductStreamId())) {

            $query = $this->connection->createQueryBuilder()
                ->select('id')
                ->from('product_stream')
                ->where('id = :product_stream_id')
                ->setParameter('product_stream_id', Uuid::fromHexToBytes($entry->getProductStreamId()));
            $result = $query->fetchOne();
            if (!empty($result)) {

                $filters = $this->productStreamBuilder->buildFilters($entry->getProductStreamId(), $context);

                $productCriteria->addFilter(...$filters);

            } else {
                return [];
            }

        } else {
            return [];
        }

        // nur Hauptprodukte
        $productCriteria->addFilter(new EqualsFilter('parentId', null));

        if ($entry->get('productSorting') == 'name_asc')
        {
            $productCriteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        }
        elseif ($entry->get('productSorting') == 'name_desc')
        {
            $productCriteria->addSorting(new FieldSorting('name', FieldSorting::DESCENDING));
        }
        elseif ($entry->get('productSorting') == 'date_asc')
        {
            $productCriteria->addSorting(new FieldSorting('releaseDate', FieldSorting::ASCENDING));
        }
        elseif ($entry->get('productSorting') == 'date_desc')
        {
            $productCriteria->addSorting(new FieldSorting('releaseDate', FieldSorting::DESCENDING));
        }
        elseif ($entry->get('productSorting') == 'price_asc')
        {
            $productCriteria->addSorting(new FieldSorting('cheapestPrice', FieldSorting::ASCENDING));
        }
        elseif ($entry->get('productSorting') == 'price_desc')
        {
            $productCriteria->addSorting(new FieldSorting('cheapestPrice', FieldSorting::DESCENDING));
        }

        if ($entry->get('productLimit') >= 0)
        {
            $productCriteria->setLimit($entry->get('productLimit'));
        }

        return $this->salesChannelProductRepository->search($productCriteria, $salesChannelContext)->getElements();
    }
}

