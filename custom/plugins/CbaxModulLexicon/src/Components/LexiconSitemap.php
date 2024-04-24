<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Components;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Defaults;
use Shopware\Core\Content\Sitemap\Exception\AlreadyLockedException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\Content\Sitemap\Service\SitemapExporterInterface;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use Psr\Log\LoggerInterface;

class LexiconSitemap
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';

    public function __construct(
        private readonly SitemapExporterInterface $sitemapExporter,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $salesChannelDomainRepository,
        private readonly EntityRepository $logEntryRepository,
        private readonly SystemConfigService $systemConfigService
	) {

    }

    public function generateSitemap(Context $context): void
    {
        $criteriaSalesChannelDomain = new Criteria();
        $salesChannelDomains = $this->salesChannelDomainRepository->search($criteriaSalesChannelDomain, $context)->getElements();

        foreach($salesChannelDomains as $salesChannelDomain) {
            $salesChannelId = $salesChannelDomain->get('salesChannelId');
            $config = $this->systemConfigService->get(self::CONFIG_PATH, $salesChannelId);
            if (empty($config['active'])) continue;
            $languageId = $salesChannelDomain->get('languageId');

            $salesChannelContext = $this->getSalesChannelContext($salesChannelId, $languageId);

            $provider = "custom-cbax-lexicon";

            $this->generateXML($salesChannelContext, $provider);
        }
    }

    private function getSalesChannelContext(string $salesChannelId, string $languageId): SalesChannelContext
    {
        return $this->salesChannelContextFactory->create('', $salesChannelId, [SalesChannelContextService::LANGUAGE_ID => $languageId]);
    }

    private function generateXML(SalesChannelContext $salesChannelContext, ?string $provider, ?int $offset = null): void
    {
        try {
            $result = $this->sitemapExporter->generate($salesChannelContext, true, $provider, $offset);

            if ($result->getOffset() !== null) {
                $this->generateXML($salesChannelContext, $provider, $result->getOffset());
            }
        } catch (AlreadyLockedException $exception) {
            $this->logger->error(sprintf('ERROR: %s', $exception->getMessage()));

            $this->logEntryRepository->create(
                [
                    [
                        'message' => sprintf('ERROR: %s', $exception->getMessage()),
                        'level' => 400,
                        'channel' => 'cbax',
                        'createdAt' => date(Defaults::STORAGE_DATE_TIME_FORMAT, time()),
                        'context' => array('cbaxLexicon')
                    ],
                ],
                $salesChannelContext->getContext()
            );
        }
    }
}
