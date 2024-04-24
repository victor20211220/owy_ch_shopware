<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Components;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;

use Cbax\ModulLexicon\Core\Content\Bundle\LexiconEntryEntity;

class LexiconHelper
{
    const ITERATOR_LIMIT = 500;

	public function __construct(
        private readonly EntityRepository $lexiconEntryRepository,
        private readonly EntityRepository $productRepository,
        private readonly Connection $connection,
        private readonly ProductStreamBuilder $productStreamBuilder
    ) {

    }

    public function saveEntry(array $entry, string $languageId, Context $context): array
    {
        //unset association sonst DB error beim Speichern
        unset($entry['products']);

        $allKeywords = [];
        $error = null;

        $newEntryKeywords = explode('+', $entry['keyword']);

        $saleschannelIds = array_map(static function ($item) {
            return $item['salesChannelId'];
        }, $entry['saleschannels']);

        //unset association sonst DB error beim speichern
        array_walk($entry['saleschannels'], function (&$value) {
            unset($value['salesChannel']);
        });

        $criteria = new Criteria();
        $criteria->setOffset(0);
        $criteria->setLimit(self::ITERATOR_LIMIT);

        $criteria->addAssociation('saleschannels');
        $criteria->addAssociation('translations');

        $criteria->addFilter(new EqualsFilter('translations.languageId', $languageId));
        $criteria->addFilter(new EqualsAnyFilter('saleschannels.salesChannelId', $saleschannelIds));

        // neuer Eintrag oder Update
        if (!empty($entry['id'])) {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('id', $entry['id'])]
            ));
        } else {
            $entry['id'] = Uuid::randomHex();
        }

        $entriesIterator = new RepositoryIterator($this->lexiconEntryRepository, $context, $criteria);

        // alle Keywords aller Einträge auflisten
        while ($entriesSearch = $entriesIterator->fetch())
        {
            $keywords = array_map(static function ($item) {
                return '+' . $item->getKeyword() . '+';
            }, $entriesSearch->getElements());
            $allKeywords = array_merge($allKeywords, $keywords);
        }

        // checken ob ein Keyword doppelt ist
        foreach ($allKeywords as $keyword)
        {
            foreach($newEntryKeywords as $newKeyWord)
            {
                if (stripos($keyword, '+' . $newKeyWord . '+') !== false)
                {
                    $error = $newKeyWord;
                    break 2;
                }
            }
        }

        if (!empty($error)) {
            return ['success' => false, 'error' => $error];
        }

        $newContext = $this->getLanguageModifiedContext($context, $languageId);

        try {
            $this->lexiconEntryRepository->upsert([$entry], $newContext);
        } catch(\Exception $exception) {
            return ['success' => false, 'error' => 'Database error: ' . $exception->getMessage()];
        }

        return ['success' => true, 'id' => $entry['id']];
    }

    public function getLanguageModifiedContext(Context $context, string $languageId): Context
    {
        $context = $context->assign([
            'languageIdChain' => array_unique(array_merge([$languageId], $context->getLanguageIdChain()))
        ]);

        return $context;
    }

    public function getAllLexiconEntries(Context $context, string $salesChannelId): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('date', [RangeFilter::LTE => date('Y-m-d H:i:s'),]));

        $criteria->addFilter(new EqualsFilter('cbax_lexicon_entry.saleschannels.salesChannelId', $salesChannelId));

        return $this->lexiconEntryRepository->search($criteria, $context)->getElements();
    }

    public function getCharByEntry(LexiconEntryEntity $entity): ?string
    {
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $title = $entity->getTranslated()['title'] ?? $entity->getTitle();
        $changedName = strtr(trim($title), ['&' => '', '#' => '', '+' => '', '-' => '', '@' => '']);
        setlocale(LC_ALL, "en_US.utf8");
        $codingChangedName = iconv('UTF-8', 'ASCII//TRANSLIT', $changedName);

        if (!empty($codingChangedName))
        {
            $changedName = $codingChangedName;
        } else {
            $changedName = strtr(trim($title),['Ä'=>'A','ä'=>'a','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o']);
        }
        $char =  ucfirst(substr($changedName, 0, 1));
        return in_array($char, $letters) ? $char : '0-9';
    }

    public function getEntry(Context $context, string $id): ?LexiconEntryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $criteria->addAssociation('products');
        $criteria->addAssociation('media2');
        $criteria->addAssociation('media3');

        $lexiconEntry = $this->lexiconEntryRepository->search($criteria, $context)->first();

        return $lexiconEntry;
    }

    //obsolet//
	public function getPagination($entries, $entry)
	{
        $entries_entries = [];
        $result = [];
		foreach($entries as $new_entry)
        {
			$entries_entries['id'] = $new_entry->get('id');
            $entries_entries['title'] = trim($new_entry->getTranslated()['title']);

			$result[] = $entries_entries;
        }

		$pagination = [];
		foreach ($result as $key => $value)
		{
			if ($value["id"] == $entry->get('id'))
			{
				$previous = $key - 1;
				if (isset($result[$previous]["id"]))
				{
					$pagination["previous"]["id"] 		= $result[$previous]["id"];
					$pagination["previous"]["title"] 	= $result[$previous]["title"];
				}


				$next = $key + 1;
				if (isset($result[$next]["id"]))
				{
					$pagination["next"]["id"] 		= $result[$next]["id"];
					$pagination["next"]["title"] 	= $result[$next]["title"];
				}

				$pagination["overview"]["id"] 		= $entry->get('id');
				$pagination['overview']["title"] 	= trim($entry->getTranslated()['title']);

				break;
			}
		}

		return $pagination;
	}

    public function updateImpressions(Context $context, LexiconEntryEntity $entry): void
    {
        $id = $entry->get('id');
        $impressions = $entry->get('impressions') + 1;

        $this->lexiconEntryRepository->update(
            [
                [ 'id' => $id, 'impressions' => $impressions ],
            ],
            $context
        );
    }

    public function getProductCountList(): array
    {
        $sql = 'SELECT Lower(HEX(`cbax_lexicon_entry_id`)), count(*) FROM `cbax_lexicon_product` WHERE 1 GROUP BY `cbax_lexicon_entry_id`';

        return $this->connection->fetchAllKeyValue($sql);
    }

    public function getProductCountStream(array $prodStreamEntries, Context $context): array
    {
        if (count($prodStreamEntries) === 0) return [];

        $streamIdsBytes = array_map(function ($item) {
            return Uuid::fromHexToBytes($item['productStreamId']);
        }, $prodStreamEntries);

        $query = $this->connection->createQueryBuilder()
            ->select('LOWER(HEX(id))')
            ->from('product_stream')
            ->where('id IN (:streamIds)')
            ->setParameter('streamIds', $streamIdsBytes, ArrayParameterType::STRING);
        $streamIds = $query->fetchFirstColumn();

        $result = [];
        $data = [];
        foreach ($prodStreamEntries as $entry) {
            if (in_array($entry['productStreamId'], $streamIds)) {
                if (empty($data[$entry['productStreamId']])) {
                    $productCriteria = new Criteria();
                    $filters = $this->productStreamBuilder->buildFilters($entry['productStreamId'], $context);
                    $productCriteria->addFilter(...$filters);

                    $result[$entry['id']] = $this->productRepository->searchIds($productCriteria, $context)->getTotal();
                    $data[$entry['productStreamId']] = $result[$entry['id']];
                } else {
                    $result[$entry['id']] = $data[$entry['productStreamId']];
                }
            }
        }

        return $result;
    }

    public function changeLexiconProducts(?string $lexiconEntryId, ?string $productId, ?string $mode): array
    {
        if (empty($productId) || empty($lexiconEntryId) || empty($mode)) return ['success' => false];

        $productId = Uuid::fromHexToBytes($productId);
        $lexiconEntryId = Uuid::fromHexToBytes($lexiconEntryId);

        if ($mode === 'add') {
            $liveVersion = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);

            $this->connection->executeStatement(
                "INSERT IGNORE INTO `cbax_lexicon_product`
                    (`cbax_lexicon_entry_id`, `product_id`, `product_version_id`)
                    VALUES (:entryId, :productId, :liveVersion);",
                [
                    'entryId' => $lexiconEntryId,
                    'productId' => $productId,
                    'liveVersion' => $liveVersion
                ]
            );

            $this->connection->executeStatement(
                "UPDATE `product` SET `cbax_lexicon_entry` = `id`
                     WHERE `id` = ? AND `cbax_lexicon_entry` IS NULL;",
                [$productId]
            );

            $this->connection->executeStatement(
                "UPDATE `cbax_lexicon_entry` SET `listing_type` = 'selected_article'
                     WHERE `id` = ? AND `listing_type` IS NULL;",
                [$lexiconEntryId]
            );

        } else {
            $this->connection->executeStatement(
                "DELETE FROM `cbax_lexicon_product` WHERE `product_id` = ? AND `cbax_lexicon_entry_id` = ?;",
                [$productId, $lexiconEntryId]
            );
        }

        return ['success' => true];
    }

    public function getLexiconProductsEntries(?string $productId): array
    {
        if (empty($productId)) return ['success' => false];

        $productId = Uuid::fromHexToBytes($productId);
        $liveVersion = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);

        $sql = "SELECT LOWER(HEX(`cbax_lexicon_entry_id`))
                FROM `cbax_lexicon_product` AS p
                INNER JOIN `cbax_lexicon_entry` AS e ON e.id = p.cbax_lexicon_entry_id AND e.`listing_type` = 'selected_article'
                WHERE p.`product_id` = ? AND p.`product_version_id` = ?;";
        $ids = $this->connection->fetchFirstColumn($sql, [$productId, $liveVersion]);

        return ['success' => true, 'lexiconEntryIds' => $ids];
    }

    //obsolet//
	// Ausgabe der Raute
	public function convertCharInSpecialCharacter($char)
    {
        if (!preg_match("#^[a-zA-Z]+$#", $char))
        {
            return '0-9';
        }
        else
        {
            return $char;
        }
    }


}
