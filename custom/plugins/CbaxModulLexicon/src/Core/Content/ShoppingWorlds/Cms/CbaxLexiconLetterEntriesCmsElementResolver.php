<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\ShoppingWorlds\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Symfony\Component\HttpFoundation\RequestStack;
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

class CbaxLexiconLetterEntriesCmsElementResolver extends AbstractCmsElementResolver
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';
    const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityRepository $lexiconEntryRepository
    ) {

    }

    public function getType(): string
    {
        return 'cbax-lexicon-letter-entries';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $char = $request->attributes->get('char');

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $config = $slot->getTranslated()['config'] ?? [];
        if (empty($slot->getFieldConfig())) {
            $slot->setFieldConfig(new FieldConfigCollection($config));
        }

        $entries = $this->getLetterLexiconEntries($salesChannelContext->getContext(), $salesChannelId, $char);
        $data = new ArrayStruct($entries);
        $slot->setData($data);
    }

    private function getLetterLexiconEntries(Context $context, string $salesChannelId, ?string $char): ?array
    {
        if (empty($char)) return [];
        $prefixes = ['&', '#', '+', '-', '@'];
        $filters = [];

        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('date', [RangeFilter::LTE => date('Y-m-d H:i:s'),]));
        $criteria->addSorting(new FieldSorting('title', FieldSorting::ASCENDING));
        $criteria->addFilter(new EqualsFilter('cbax_lexicon_entry.saleschannels.salesChannelId', $salesChannelId));

        if ($char !== '0-9') {

            $filters[] = new PrefixFilter('title', $char);
            foreach ($prefixes as $prefix) {
                $filters[] = new PrefixFilter('title', $prefix . $char);
            }

            $criteria->addFilter(
                new MultiFilter(
                    MultiFilter::CONNECTION_OR,
                    $filters
                )
            );

        } else {
            foreach (self::LETTERS as $letter) {
                $filters[] = new PrefixFilter('title', $letter);
                foreach ($prefixes as $prefix) {
                    $filters[] = new PrefixFilter('title', $prefix . $letter);
                }
            }

            $criteria->addFilter(
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    $filters
                )
            );
        }

        return $this->lexiconEntryRepository->search($criteria, $context)->getElements();
    }
}

