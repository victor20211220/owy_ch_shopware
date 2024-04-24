<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Components;

use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

use Shopware\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use Cbax\ModulLexicon\Bootstrap\DefaultConfig;

class LexiconSeo
{
    /**
     * Damit die Componente arbeiten kann muss der Routerpfad und der Pfad nach folgendem Schema aufgebaut sein
     * (als Beispiel hier manufacturer)
     * Routername: 'frontend.cbax.manufacturer.detail'
     * Routerpfad: '/cbax/manufacturer/detail/{id};
     * -->
     * Route("/cbax/manufacturer/detail/{id?}", name="frontend.cbax.manufacturer.detail",  options={"seo"=true}, methods={"GET"})
     */
    // SERVICE anlegen und EntityRepository (z.B. Hersteller) ändern
    const MODUL_NAME = 'CbaxModulLexicon';
    const ENTITY_NAME = 'lexicon';
    const INDEX_PAGE = true;
    const DETAIL_PAGE = true;
    const LISTING_PAGES = true;
    const AJAX_PAGES = false;
    const ENTITY_TRANSLATED_TITLE_FIELD = 'title'; // meistens Name oder Title
    const INDEX_EMPTY_SEO_PATH = 'lexikon';
    const CONTENTS_EMPTY_SEO_PATH = 'Inhaltsverzeichnis';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $seoUrlRepository,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $entityRepository,
        private readonly Slugify $slugify,
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $logEntryRepository,
        private readonly TranslatorInterface $translator,
        private readonly EntityRepository $languageRepository,
        private readonly Connection $connection
    ) {

    }

	public function getSeoUrls(string $route_name, Context $context, SalesChannelContext $salesChannelContext): array
	{
		$salesChannelId = $salesChannelContext->getSalesChannelId();
		$languageId 	= $salesChannelContext->getSalesChannel()->getLanguageId();

		$criteria = new Criteria();
		$criteria->addFilter(new EqualsFilter('languageId', $languageId));
		$criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
		$criteria->addFilter(new EqualsFilter('routeName', $route_name));
        $criteria->addFilter(new EqualsFilter('isDeleted', 0));
        $criteria->addFilter(new EqualsFilter('isCanonical', 1));

		$seoUrls = $this->seoUrlRepository->search($criteria, $context)->getElements();

        $allSeoUrls = [];

		foreach($seoUrls as $seoUrl)
		{
			$allSeoUrls[$seoUrl->get('pathInfo')] = $seoUrl->get('seoPathInfo');
		}

		return $allSeoUrls;
	}

    public function deleteSeoUrls(): array
    {
        $sql = "DELETE FROM seo_url WHERE path_info LIKE '/cbax/" . self::ENTITY_NAME . "/%'";
        try {
            $this->connection->executeStatement($sql);
            return ['success' => true];
        }
        catch (\Exception) {
            return ['success' => false];
        }
    }

	public function getSeoUrl(string $route_name, string $path_info, Context $context, Request $request): string
	{
		$salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        $shop_url 			= $request->attributes->get(RequestTransformer::STOREFRONT_URL);
		$salesChannelId 	= $salesChannelContext->getSalesChannelId();
		$languageId 		= $salesChannelContext->getSalesChannel()->getLanguageId();

		$criteria = new Criteria();
		$criteria->addFilter(new EqualsFilter('languageId', $languageId));
		$criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
		$criteria->addFilter(new EqualsFilter('routeName', $route_name));
		$criteria->addFilter(new EqualsFilter('pathInfo', $path_info));
		$criteria->addFilter(new EqualsFilter('isDeleted', 0));
        $criteria->addFilter(new EqualsFilter('isCanonical', 1));

		$seoUrl = $this->seoUrlRepository->search($criteria, $context)->first();

		if (!empty($seoUrl))
		{
			return $shop_url .'/'. $seoUrl->get('seoPathInfo');
		}
		else
		{
			return $shop_url . $path_info;
		}
	}

    public function generateSeoUrls(Context $context, string $adminLocalLanguage = 'en-GB'): array
    {
        // SEO Urls erstellen
        $urls = $this->createSeoUrls($context, $adminLocalLanguage);

        return $urls;
    }

    /**
     * Tries to get the snippet and if the snippet returns its ID (which is equal to the snippetName), looks if fallbackSnippet was given and returns it.
     * Else returns the original snippetName.
     *
     * Can be used to check if a snippet is set and if not use a default value.
     */
    public function tryGetSnippet(string $snippetName, ?string $locale = null, ?string $fallbackSnippet = null, string $defaultLocale = "en-GB"): string
    {
        $localeSnippet = $this->translator->trans($snippetName, [], null, $locale);

        $localeSnippet = !empty(trim($localeSnippet)) ? $localeSnippet : $this->translator->trans($snippetName, [], null, $defaultLocale);

        if ($localeSnippet === $snippetName) {
            $localeSnippet = $this->translator->trans($snippetName, [], null, $defaultLocale);
        }

        $localeSnippet = !empty(trim($localeSnippet)) ? $localeSnippet : $snippetName;

        if ($localeSnippet === $snippetName and $fallbackSnippet !== null) {
            $localeSnippet = $fallbackSnippet;
        }

        return strtolower($localeSnippet);
    }

    public function createSeoUrls(Context $context, ?string $locale): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('saleschannels');
        $criteria->addAssociation('translations');
        $entities = $this->entityRepository->search($criteria, $context)->getElements();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        $criteria->addAssociation('languages');
        /** @var array<SalesChannelEntity> $salesChannels */
        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getElements();

        $languageCriteria = new Criteria();
        $languageCriteria->addAssociation('locale');
        /** @var array<LanguageEntity> $languagesWithLocales */
        $languagesWithLocales = $this->languageRepository->search($languageCriteria,$context)->getElements();

        // Shopware's getFallback() in translator->trans function does not get the locale correctly
        $defaultLocale = $languagesWithLocales[Defaults::LANGUAGE_SYSTEM]->getLocale()->getCode();

        if (self::LISTING_PAGES) {
            // Listing/Navigation
            $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0-9');
        }

        /////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Erstellung eines Array mit allen Hersteller Seos
        $newSeoUrls = [];
        $errorSeoUrls = [];

        foreach ($salesChannels as $salesChannel)
        {
            $salesChannelId 	= $salesChannel->getId();

            $config = $this->systemConfigService->get(self::MODUL_NAME, $salesChannelId)['config'];
            if (empty($config['active'])) continue;

            $base_path_index = strtolower(self::INDEX_EMPTY_SEO_PATH);
            $base_path_contents = strtolower(self::CONTENTS_EMPTY_SEO_PATH);
            $trailingSlash = !empty($config['trailingSlash']) ? '/' : '';

            $languages 			= $salesChannel->getLanguages();
            $languageIds 		= $languages->getIds();

            foreach ($languageIds as $languageId)
            {
                $localeBasePathIndex = $base_path_index;
                $localeBasePathContent = $base_path_contents;

                if (!empty($languagesWithLocales[$languageId]))
                {
                    $languageLocalCode = $languagesWithLocales[$languageId]->getLocale()->getCode();

                    $localeBasePathIndex = $this->tryGetSnippet(DefaultConfig::BASE_PATH_SNIPPET, $languageLocalCode, $base_path_index, $defaultLocale);
                    $localeBasePathContent = $this->tryGetSnippet(DefaultConfig::BASE_PATH_CONTENTS_SNIPPET, $languageLocalCode, $base_path_contents, $defaultLocale);
                }

                //index
                if (self::INDEX_PAGE)
                {
                    // Startseite
                    $newSeoUrls[] = [
                        'languageId' => $languageId,
                        'salesChannelId' => $salesChannelId,
                        'foreignKey' => Uuid::randomHex(),
                        'routeName' => 'frontend.cbax.'.self::ENTITY_NAME.'.index',
                        'pathInfo' => '/cbax/'.self::ENTITY_NAME.'/index',
                        'seoPathInfo' => $localeBasePathIndex . $trailingSlash,
                        'isCanonical' => true,
                        'isDeleted' => false
                    ];
                    // Inhaltsverzeichnis
                    $newSeoUrls[] = [
                        'languageId' => $languageId,
                        'salesChannelId' => $salesChannelId,
                        'foreignKey' => Uuid::randomHex(),
                        'routeName' => 'frontend.cbax.'.self::ENTITY_NAME.'.content',
                        'pathInfo' => '/cbax/'.self::ENTITY_NAME.'/content',
                        'seoPathInfo' => $localeBasePathIndex . '/' . $localeBasePathContent . $trailingSlash,
                        'isCanonical' => true,
                        'isDeleted' => false
                    ];
                }

                if (self::DETAIL_PAGE || self::AJAX_PAGES)
                {
                    $titlesDone = [];

                    foreach ($entities as $entry)
                    {
                        $entrySalesChannelIds = [];
                        $entitySalesChannelElements = $entry->get('saleschannels')?->getElements();

                        // salesChannelId holen, um die Detailseiten anhand der salesChannelIds zu erstellen
                        foreach ($entitySalesChannelElements as $channelElement) {
                            $entrySalesChannelIds[] = $channelElement->get('salesChannelId');
                        }

                        if (in_array($salesChannelId, $entrySalesChannelIds)) {
                            $translatedTitle = null;
                            $tranlation = $entry->getTranslations()?->filterByLanguageId($languageId)?->first();

                            if (!empty($tranlation)) {
                                $translatedTitle = $tranlation->get(self::ENTITY_TRANSLATED_TITLE_FIELD);
                            }
                            if (empty($translatedTitle)) {
                                $translatedTitle = $entry->getTranslated()[self::ENTITY_TRANSLATED_TITLE_FIELD];
                            }

                            $title = strtolower($this->sCleanupPath($translatedTitle));
                            if (in_array($title, $titlesDone))
                            {
                                if (self::DETAIL_PAGE)
                                {
                                    $errorSeoUrls[] = [
                                        'error' => $this->getMessageText('seo.sameNameLexiconEntry', $locale),
                                        self::ENTITY_TRANSLATED_TITLE_FIELD => $entry->getTranslated()[self::ENTITY_TRANSLATED_TITLE_FIELD],
                                        'saleschannel' => $salesChannels[$salesChannelId]->getTranslated()['name'],
                                        'language' => $languages->getElements()[$languageId]->getName(),
                                        'pathInfo' => '/cbax/' . self::ENTITY_NAME . '/detail/' . $entry->get('id'),
                                        'seoPathInfo' => strtolower($localeBasePathIndex . '/' . $title) . $trailingSlash
                                    ];
                                }

                                if (self::AJAX_PAGES)
                                {
                                    $errorSeoUrls[] = [
                                        'error' => $this->getMessageText('seo.sameNameLexiconEntry', $locale),
                                        self::ENTITY_TRANSLATED_TITLE_FIELD => $entry->getTranslated()[self::ENTITY_TRANSLATED_TITLE_FIELD],
                                        'saleschannel' => $salesChannels[$salesChannelId]->getTranslated()['name'],
                                        'language' => $languages->getElements()[$languageId]->getName(),
                                        'pathInfo' => '/cbax/' . self::ENTITY_NAME . '/ajax/' . $entry->get('id'),
                                        'seoPathInfo' => strtolower($localeBasePathIndex . '-' . $title) . $trailingSlash
                                    ];
                                }

                                continue;
                            }
                            $titlesDone[] = $title;

                            // SEO Url Detailseite erstellen
                            if (self::DETAIL_PAGE)
                            {
                                $newSeoUrls[] = [
                                    'languageId' => $languageId,
                                    'salesChannelId' => $salesChannelId,
                                    'foreignKey' => $entry->get('id'),
                                    'routeName' => 'frontend.cbax.' . self::ENTITY_NAME . '.detail',
                                    'pathInfo' => '/cbax/' . self::ENTITY_NAME . '/detail/' . $entry->get('id'),
                                    'seoPathInfo' => strtolower($localeBasePathIndex . '/' . $title) . $trailingSlash,
                                    'isCanonical' => true,
                                    'isDeleted' => false
                                ];
                            }

                            // SEO Url Detailseite erstellen
                            if (self::AJAX_PAGES)
                            {
                                $newSeoUrls[] = [
                                    'languageId' => $languageId,
                                    'salesChannelId' => $salesChannelId,
                                    'foreignKey' => $entry->get('id'),
                                    'routeName' => 'frontend.cbax.' . self::ENTITY_NAME . '.ajax',
                                    'pathInfo' => '/cbax/' . self::ENTITY_NAME . '/ajax/' . $entry->get('id'),
                                    'seoPathInfo' => strtolower($localeBasePathIndex . '-' . $title) . $trailingSlash,
                                    'isCanonical' => true,
                                    'isDeleted' => false
                                ];
                            }
                        }
                    }
                }

                // SEO Url Listing/Navigation erstellen
                if (self::LISTING_PAGES)
                {
                    foreach ($letters as $letter)
                    {
                        $newSeoUrls[] = [
                            'languageId' => $languageId,
                            'salesChannelId' => $salesChannelId,
                            'foreignKey' => Uuid::randomHex(),
                            'routeName' => 'frontend.cbax.'.self::ENTITY_NAME.'.listing',
                            'pathInfo' => '/cbax/'.self::ENTITY_NAME.'/listing/' . $letter,
                            'seoPathInfo' => strtolower($localeBasePathIndex . '/' . $letter) . $trailingSlash,
                            'isCanonical' => true,
                            'isDeleted' => false
                        ];
                    }
                }
            }
        }

        /////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Vor Erzeugung Abgleich mit bereits bestehenden seos, Namenskonflikte vermeiden, Updates nur wenn nötig
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsAnyFilter('routeName', [
            'frontend.cbax.' . self::ENTITY_NAME . '.index',
            'frontend.cbax.' . self::ENTITY_NAME . '.detail',
            'frontend.cbax.' . self::ENTITY_NAME . '.listing',
            'frontend.cbax.' . self::ENTITY_NAME . '.ajax',
            'frontend.cbax.' . self::ENTITY_NAME . '.content'
        ]));

        // Seos die vom Plugin schon erstellt wurden
        /* @var SeoUrlCollection $oldSeoUrls */
        $oldSeoUrls = $this->seoUrlRepository->search($criteria, $context);

        $deleteInactiveSeoUrls = [];
        $createSeoUrls = [];
        $updateSeoUrls = [];
        $seoCounter = 0;

        $oldSeoUrlsCanonical = ($oldSeoUrls->filterByProperty('isCanonical', true))->getElements();
        $oldSeoUrlsNonCanonical = ($oldSeoUrls->filterByProperty('isCanonical', false))->getElements();

        foreach ($newSeoUrls as $seo)
        {
            $done = false;

            /* @var SeoUrlEntity $oldSeo */
            foreach ($oldSeoUrlsCanonical as $id => $oldSeo)
            {
                // suche seo unter bestehenden canonical seos zuerst
                if (
                    $seo['languageId'] == $oldSeo->getLanguageId() &&
                    $seo['salesChannelId'] == $oldSeo->getSalesChannelId() &&
                    $seo['routeName'] == $oldSeo->getRouteName() &&
                    $seo['pathInfo'] == $oldSeo->getPathInfo()
                )
                {
                    $done = true;
                    // seo vorhanden, nichts tun
                    if ($seo['seoPathInfo'] == $oldSeo->getSeoPathInfo() && empty($oldSeo->getIsDeleted()))
                    {
                        unset($oldSeoUrlsCanonical[$id]);
                        $seoCounter++;
                        break;
                    }
                    elseif ($seo['seoPathInfo'] == $oldSeo->getSeoPathInfo())
                        // vorhandene seo updaten, sollte nie vorkommen
                    {
                        $seo['id'] = $id;
                        $updateSeoUrls[] = $seo;
                        unset($oldSeoUrlsCanonical[$id]);
                        break;
                    } // seo vorhanden mit anderen Pfad
                    elseif ($seo['seoPathInfo'] != $oldSeo->getSeoPathInfo())
                    {
                        // vorhandene seo updaten oder isCanonical 0 setzen und seo neu erstellen, vorher seo_path_info checken wegen unique contraint
                        $otherSeoWithThisPath = $this->findOtherSeoWithThisPath($seo, $context);
                        if (empty($otherSeoWithThisPath) || (!empty($otherSeoWithThisPath) && ($otherSeoWithThisPath->getIsDeleted() || empty($otherSeoWithThisPath->getIsCanonical()))))
                        {
                            // neuer Pfad vergeben an andere seo, die aber isDeleted oder nicht isCanonical, daher löschen
                            if (!empty($otherSeoWithThisPath) && ($otherSeoWithThisPath->getIsDeleted() || empty($otherSeoWithThisPath->getIsCanonical())))
                            {
                                $deleteInactiveSeoUrls[] = ['id' => $otherSeoWithThisPath->getId()];
                            }
                            // alte seo nicht üpberschreiben, seo neu erstellen, alte auf non canonical setzen
                            if ($oldSeo->getIsDeleted() == 0)
                            {
                                $seo['id'] = Uuid::randomHex();
                                $createSeoUrls[] = $seo;

                                $updateSeoUrls[] = [
                                    'id' => $id,
                                    'isCanonical' => NULL
                                ];

                                // überschreiben, sollte nie vorkommen
                            } else {
                                $seo['id'] = $id;
                                $updateSeoUrls[] = $seo;
                            }

                            unset($oldSeoUrlsCanonical[$id]);
                            break;
                        } else //andere  canonical seo url verhindert neue wegen gleichem Pfad -> error
                        {
                            $errorSeoUrls[] = [
                                'error' => $this->getBlockingSeoErrorMessage($otherSeoWithThisPath, $locale),
                                'seoPathInfo' => $seo['seoPathInfo'],
                                'saleschannel' => $salesChannels[$seo['salesChannelId']]->getTranslated()['name'],
                                'language' => $salesChannels[$seo['salesChannelId']]->getLanguages()->getElements()[$seo['languageId']]->getName(),
                                'pathInfo' => $seo['pathInfo']
                            ];
                            unset($oldSeoUrlsCanonical[$id]);
                            break;
                        }
                    }
                }
            }

            if ($done) continue;

            /* @var SeoUrlEntity $oldSeo */
            foreach ($oldSeoUrlsNonCanonical as $id => $oldSeo)
            {
                // suche nach seo unter non canonicals, wenn nicht gefunden unter canonicals
                if (
                    $seo['languageId'] == $oldSeo->getLanguageId() &&
                    $seo['salesChannelId'] == $oldSeo->getSalesChannelId() &&
                    $seo['routeName'] == $oldSeo->getRouteName() &&
                    $seo['pathInfo'] == $oldSeo->getPathInfo() &&
                    $seo['seoPathInfo'] == $oldSeo->getSeoPathInfo()
                )
                {
                    // non canonical auf canonical setzen
                    $seo['id'] = $id;
                    $updateSeoUrls[] = $seo;
                    unset($oldSeoUrlsNonCanonical[$id]);
                    $done = true;
                    break;
                }
            }

            if ($done) continue;

            //seo url komplett neu, checken auf andere seo_path_info
            $otherSeoWithThisPath = $this->findOtherSeoWithThisPath($seo, $context);
            if (!empty($otherSeoWithThisPath))
            {
                //andere seo url überschreiben
                if ($otherSeoWithThisPath->getIsDeleted() || empty($otherSeoWithThisPath->getIsCanonical()))
                {
                    $seo['id'] = $otherSeoWithThisPath->getId();
                    $updateSeoUrls[] = $seo;
                } else
                    //andere seo url verhindert neue wegen gleichem Pfad -> error
                {
                    $errorSeoUrls[] = [
                        'error' => $this->getBlockingSeoErrorMessage($otherSeoWithThisPath, $locale),
                        'seoPathInfo' => $seo['seoPathInfo'],
                        'saleschannel' => $salesChannels[$seo['salesChannelId']]->getTranslated()['name'],
                        'language' => $salesChannels[$seo['salesChannelId']]->getLanguages()->getElements()[$seo['languageId']]->getName(),
                        'pathInfo' => $seo['pathInfo']
                    ];
                }
            } else
                //neue Seo url wird created
            {
                $seo['id'] = Uuid::randomHex();
                $createSeoUrls[] = $seo;
            }
        }

        //in $oldSeoUrls übrig gebliebene seos sind obsolete -> delete
        $remainingOldSeoUrls = array_merge($oldSeoUrlsNonCanonical, $oldSeoUrlsCanonical);
        if (count($remainingOldSeoUrls) > 0)
        {
            $obsoleteLexiconSeoIds = array_map(static function ($key) {
                return ['id' => $key];
            }, array_keys($remainingOldSeoUrls));
            $this->seoUrlRepository->delete($obsoleteLexiconSeoIds, $context);
        }

        $this->seoUrlRepository->delete($deleteInactiveSeoUrls, $context);
        $this->seoUrlRepository->update($updateSeoUrls, $context);
        $this->seoUrlRepository->create($createSeoUrls, $context);

        $newCounter = count($updateSeoUrls) + count($createSeoUrls);
        $message = $newCounter . $this->getMessageText('seo.MessageCreated', $locale) . $seoCounter . $this->getMessageText('seo.MessageChecked', $locale);
        $errorMessage = count($errorSeoUrls) > 0 ? count($errorSeoUrls) . $this->getMessageText('seo.errorMessage', $locale) : '';

        // $newCounter: neu erstellt oder updated, $seoCounter: schon vorhanden, nicht geändert //
        if (count($errorSeoUrls) > 0) {
            $this->errorLogging($errorSeoUrls, $errorMessage, $context);
        }

        return ['message' => $message, 'errorMessage' => $errorMessage, 'errors' => $errorSeoUrls];
    }

    public function getMessageText(string $snippetPath, ?string $locale): string
    {
        if (!in_array($locale, ['en-GB', 'de-DE'])) {
            $locale = 'en-GB';
        }

        $cbaxLexiconCatalog = $this->translator->getCatalogue($locale)->all('cbaxLexicon');

        if (!empty($cbaxLexiconCatalog[$snippetPath])) {
            return $cbaxLexiconCatalog[$snippetPath];

        } else {
            return $snippetPath;
        }
    }

    private function errorLogging(array $errorSeoUrls, string $errorMessage, Context $context): void
    {
        $this->logger->critical($errorMessage, $errorSeoUrls);
        $this->logEntryRepository->create(
            [
                [
                    'message' => $errorMessage,
                    'level' => 400,
                    'channel' => 'cbax Lexicon',
                    'createdAt' => date(Defaults::STORAGE_DATE_TIME_FORMAT, time()),
                    'context' => $errorSeoUrls
                ],
            ],
            $context
        );
    }

    private function getBlockingSeoErrorMessage(SeoUrlEntity $seoUrl, ?string $locale): string
    {
        $routeName = $seoUrl->getRouteName();
        $message = $this->getMessageText('seo.blockingSeoStart', $locale) . $seoUrl->getSeoPathInfo();

        if ($routeName === 'frontend.navigation.page') {
            $message .= $this->getMessageText('seo.sameNameNavigation', $locale) . $seoUrl->getForeignKey() . '.';

        } elseif ($routeName === 'frontend.detail.page') {
            $message .= $this->getMessageText('seo.sameNameProduct', $locale) . $seoUrl->getForeignKey() . '.';

        } elseif ($routeName === 'frontend.landing.page') {
            $message .= $this->getMessageText('seo.sameNameLanding', $locale) . $seoUrl->getForeignKey() . '.';

        } else {
            $message .= $this->getMessageText('seo.sameNameOthers', $locale) . $routeName . $this->getMessageText('seo.sameNameOthersEnd', $locale) . $seoUrl->getPathInfo() . '.';
        }
        return $message;
    }

    private function findOtherSeoWithThisPath(array $seo, Context $context): ?SeoUrlEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('seoPathInfo', $seo['seoPathInfo']));
        $criteria->addFilter(new EqualsFilter('languageId', $seo['languageId']));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $seo['salesChannelId']));
        return $this->seoUrlRepository->search($criteria, $context)->first();
    }

	/**
     * Replace special chars with a URL compliant representation
     */
    public function sCleanupPath(string $path): string
    {
        $parts = explode('/', $path);
        $parts = array_map(function ($path) {
            return $this->slugify->slugify($path);
        }, $parts);

        $path = implode('/', $parts);
        $path = strtr($path, ['-.' => '.']);

        return $path;
    }
}
