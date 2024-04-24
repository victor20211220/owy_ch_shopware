<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Sitemap\Provider;

use Shopware\Core\Content\Sitemap\Provider\AbstractUrlProvider;
use Shopware\Core\Content\Sitemap\Struct\Url;
use Shopware\Core\Content\Sitemap\Struct\UrlResult;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomUrlProvider extends AbstractUrlProvider
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';
    const ROUTE_NAME = 'cbax.lexicon';
    const SITEMAP_XML_NAME = 'custom-cbax-lexicon';

    private $total = 0;

    public function __construct(
        private readonly EntityRepository $seoUrlsRepository,
        private readonly SystemConfigService $systemConfigService
    ) {

    }

    public function getName(): string
    {
        return self::SITEMAP_XML_NAME;
    }

    // ist notwendig!
    public function getDecorated(): AbstractUrlProvider
    {
        throw new DecorationPatternException(self::class);
    }

     /**
     * {@inheritdoc}
     */
    public function getUrls(SalesChannelContext $context, int $limit, ?int $offset = null): UrlResult
    {
        $urls = [];
        $pluginConfig = $this->systemConfigService->get(self::CONFIG_PATH, $context->getSalesChannelId());

        if (empty($pluginConfig['sitemapGenerate']) || empty($pluginConfig['active'])) {
            return new UrlResult($urls, $offset);
        }

        // holen und formatieren der Daten
        $sitemapCustomUrls = $this->getLexikonSeoUrls($context, $limit, $offset);

        $url = new Url();
        foreach ($sitemapCustomUrls as $sitemapCustomUrl) {
            if (!$this->isAvailableForSalesChannel($sitemapCustomUrl, $context->getSalesChannel()->getId())) {
                continue;
            }

            $newUrl = clone $url;
            $newUrl->setLoc($sitemapCustomUrl['url']);
            $newUrl->setLastmod($sitemapCustomUrl['lastMod']);
            $newUrl->setChangefreq($sitemapCustomUrl['changeFreq']);
            $newUrl->setResource(self::SITEMAP_XML_NAME);
            $newUrl->setIdentifier('');

            $urls[] = $newUrl;
        }

        $newOffset = null;

        if ($offset + $limit < $this->total) {
            $newOffset = $offset + $limit;
        }

        return new UrlResult($urls, $newOffset);
    }

    private function isAvailableForSalesChannel(array $url, ?string $salesChannelId): bool
    {
        return \in_array($url['salesChannelId'], [$salesChannelId, null], true);
    }

    private function getLexikonSeoUrls($salesChannelContext, $limit, $offset): array
    {
        $urls = [];

        $context = $salesChannelContext->getContext();
        $salesChannelId = $salesChannelContext->getSalesChannel()->get('id');
        $languageId = $salesChannelContext->getSalesChannel()->get('languageId');

        $criteria = new Criteria();
        $criteria->setLimit($limit);
        if ($offset !== null) {
            $criteria->setOffset($offset);
        }
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        $criteria->addFilter(new ContainsFilter('routeName', self::ROUTE_NAME));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addFilter(new EqualsFilter('languageId', $languageId));
        // nur aktive Urls
        $criteria->addFilter(new EqualsFilter('isCanonical', 1));
        // Ajax Url ausschließen
		$criteria->addFilter(new NotFilter(
    		NotFilter::CONNECTION_AND,
    		[new EqualsFilter('routeName', 'frontend.cbax.lexicon.ajax')]
		));

        $seoUrlsResult = $this->seoUrlsRepository->search($criteria, $context);

        $this->total = $seoUrlsResult->getTotal();

        foreach ($seoUrlsResult->getElements() as $seoUrl) {
            $url = [];
            $url['lastMod'] = ($seoUrl->get('updatedAt') != null) ? $seoUrl->get('updatedAt') : $seoUrl->get('createdAt') ;
            $url['salesChannelId'] = $salesChannelId;
            $url['changeFreq'] = 'daily';
            $url['url'] = $seoUrl->get('seoPathInfo');
            $urls[] = $url;
        }

        return $urls;
    }
}
