<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\ShoppingWorlds\Cms;

use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;

class CbaxLexiconPopularEntriesCmsElementResolver extends AbstractCmsElementResolver
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityRepository $lexiconEntryRepository
    ) {

    }

    public function getType(): string
    {
        return 'cbax-lexicon-popular-entries';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $request = $this->requestStack->getCurrentRequest();

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $config = $slot->getTranslated()['config'] ?? [];
        $limit = $config['entryNumber']['value'] ?? 3;
        if (empty($slot->getFieldConfig())) {
            $slot->setFieldConfig(new FieldConfigCollection($config));
        }

        $entries = $this->getPopularLexiconEntries($salesChannelContext->getContext(), $salesChannelId, $limit);
        $data = new ArrayStruct($entries);
        $slot->setData($data);
    }

    private function getPopularLexiconEntries(Context $context, string $salesChannelId, int $limit = 3): ?array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->addFilter(new RangeFilter('date', [RangeFilter::LTE => date('Y-m-d H:i:s'),]));
        $criteria->addSorting(new FieldSorting('impressions', FieldSorting::DESCENDING));
        $criteria->addFilter(new EqualsFilter('cbax_lexicon_entry.saleschannels.salesChannelId', $salesChannelId));

        return $this->lexiconEntryRepository->search($criteria, $context)->getElements();
    }
}

