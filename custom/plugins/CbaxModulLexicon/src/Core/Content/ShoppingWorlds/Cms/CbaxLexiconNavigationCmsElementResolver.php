<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\ShoppingWorlds\Cms;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
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
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;

use Cbax\ModulLexicon\Components\LexiconSeo;

class CbaxLexiconNavigationCmsElementResolver extends AbstractCmsElementResolver
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityRepository $lexiconEntryRepository,
        private readonly LexiconSeo $lexiconSeo
    ) {

    }

    public function getType(): string
    {
        return 'cbax-lexicon-navigation';
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
        if (empty($slot->getFieldConfig())) {
            $slot->setFieldConfig(new FieldConfigCollection($config));
        }

        $entriesByChar = $this->getNavigationData($salesChannelContext->getContext(), $salesChannelId, $request);

        $data = new ArrayStruct($entriesByChar);
        $slot->setData($data);
    }

    private function getNavigationData(Context $context, string $salesChannelId, Request $request): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('date', [RangeFilter::LTE => date('Y-m-d H:i:s'),]));
        $criteria->addFilter(new EqualsFilter('cbax_lexicon_entry.saleschannels.salesChannelId', $salesChannelId));

        $lexiconEntries = $this->lexiconEntryRepository->search($criteria, $context)->getElements();

        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

        $result = [];
        $result['0-9'] = [
            'char' => 			'0-9',
            'countEntries' => 	0,
            'link' => 			'#'
        ];

        foreach ($letters as $letter) {
            $result[$letter] = [
                'char' => 			$letter,
                'countEntries' => 	0,
                'link' => 			'#'
            ];
        }

        foreach ($lexiconEntries as $entry)
        {
            $changedName = strtr(trim($entry->getTranslated()['title']), ['&' => '', '#' => '', '+' => '', '-' => '', '@' => '']);
            setlocale(LC_ALL, "en_US.utf8");
            $codingChangedName = iconv('UTF-8', 'ASCII//TRANSLIT', $changedName);

            if (!empty($codingChangedName)) {
                $changedName = $codingChangedName;
            } else {
                $changedName = strtr(trim($entry->getTranslated()['title']),['Ä'=>'A','ä'=>'a','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o']);
            }
            $letter = ucfirst(substr($changedName, 0, 1));

            // prüfen ob das erste Zeichen bereits in der Liste gesetzt wurde
            if (in_array($letter, $letters) && $result[$letter]['countEntries'] === 0) {
                $result[$letter]['countEntries'] = 1;
                $result[$letter]['link'] = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.listing' , '/cbax/lexicon/listing/' . $letter, $context, $request);
            } else if (!in_array($letter, $letters) && $result['0-9']['countEntries'] === 0) {
                // Zeichen, die nicht in $letters enthalten sind unter '#' zusammenfassen
                $result['0-9']['countEntries'] = 1;
                $result['0-9']['link'] = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.listing' , '/cbax/lexicon/listing/' . '0-9', $context, $request);
            }
        }

        return array_values($result);
    }
}

