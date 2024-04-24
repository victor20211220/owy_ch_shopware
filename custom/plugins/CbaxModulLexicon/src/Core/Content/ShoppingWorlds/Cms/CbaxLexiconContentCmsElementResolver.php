<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\ShoppingWorlds\Cms;

use Symfony\Component\HttpFoundation\Request;
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
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;

use Cbax\ModulLexicon\Components\LexiconSeo;

class CbaxLexiconContentCmsElementResolver extends AbstractCmsElementResolver
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
        return 'cbax-lexicon-content';
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

        $entries = $this->getAllLexiconEntries($salesChannelContext->getContext(), $salesChannelId, $request);
        $data = new ArrayStruct($entries);
        $slot->setData($data);
    }

    private function getAllLexiconEntries(Context $context, string $salesChannelId, Request $request): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('date', [RangeFilter::LTE => date('Y-m-d H:i:s'),]));
        $criteria->addFilter(new EqualsFilter('cbax_lexicon_entry.saleschannels.salesChannelId', $salesChannelId));
        $lexiconEntries = $this->lexiconEntryRepository->search($criteria, $context)->getElements();

        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

        $sortedEntries = ['letters' => []];

        foreach ($lexiconEntries as $entry)
        {
            $changedName = strtr(trim($entry->getTranslated()['title']), ['&' => '', '#' => '', '+' => '', '-' => '', '@' => '']);
            setlocale(LC_ALL, "en_US.utf8");
            $codingChangedName = iconv('UTF-8', 'ASCII//TRANSLIT', $changedName);

            if (!empty($codingChangedName))
            {
                $changedName = $codingChangedName;
            } else {
                $changedName = strtr(trim($entry->getTranslated()['title']),['Ä'=>'A','ä'=>'a','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o']);
            }
            $letter = ucfirst(substr($changedName, 0, 1));

            // prüfen ob das erste Zeichen bereits in der Liste existiert und ggf. initiales leeres Array anlegen
            if (empty($sortedEntries['letters'][$letter]) && in_array($letter, $letters)) {
                $sortedEntries['letters'][$letter] = [];
            } else if (!in_array($letter, $letters)) {
                // Zeichen, die nicht in $letters enthalten sind unter '#' zusammenfassen
                if (!isset($sortedEntries['letters']['#'])) {
                    $sortedEntries['letters']['#'] = [];
                }
                $letter = '#';
            }
            array_push($sortedEntries['letters'][$letter], $entry);
        }

        // sortieren der Liste
        ksort($sortedEntries['letters']);

        $result = [];

        // Eintrag für Sonderzeichen anlegen
        if (!empty($sortedEntries['letters']['#'])) {
            $res0 = $sortedEntries['letters']['#'];
        } else {
            $res0 = [];
        }

        $result[0] = [
            'char' => 			'0-9',
            'countEntries' => 	count($res0),
            'link' => 			$this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.listing' , '/cbax/lexicon/listing/' . '0-9', $context, $request),
            'items' => 			$this->sortLetterEntries($res0)
        ];

        // Einträge für $letters anlegen
        foreach ($letters as $letter) {
            if (!empty($sortedEntries['letters'][$letter])) {
                $res = $sortedEntries['letters'][$letter];

                array_push($result, [
                    'char'          => $letter,
                    'countEntries'  => count($res),
                    'link' 			=> $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.listing' , '/cbax/lexicon/listing/' . $letter, $context, $request),
                    'items'         => $this->sortLetterEntries($res),
                ]);
            } else {
                array_push($result, [
                    'char' 			=> $letter,
                    'countEntries' 	=> 0,
                    'link' 			=> '#',
                    'items'         => []
                ]);
            }
        }

        return $result;
    }

    private function sortLetterEntries(array $entries): array
    {
        if (empty($entries))
        {
            return $entries;
        }

        usort($entries, function($a, $b)
        {
            if (strtolower($a->getTranslated()['title']) == strtolower($b->getTranslated()['title']))
            {
                return 0;
            }

            return (strtolower($a->getTranslated()['title']) < strtolower($b->getTranslated()['title'])) ? -1 : 1;
        });

        return $entries;
    }
}

