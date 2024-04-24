<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\ScheduledTask;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;

use Cbax\ModulLexicon\Components\LexiconSeo;

#[AsMessageHandler(handles: SeoIndex::class)]
class SeoIndexHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $logEntryRepository,
        private readonly LexiconSeo $lexiconSeo,
        protected readonly CacheClearer $cacheClearer
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public static function getHandledMessages(): iterable
    {
        return [ SeoIndex::class ];
    }

    public function run(): void
    {
        $this->cacheClearer->clear();
        $context = Context::createDefaultContext();

        try {
            $result = $this->lexiconSeo->generateSeoUrls($context);
        } catch (\Throwable $e) {
            // catch exception - otherwise the task will never be called again
            $this->logger->critical($e->getMessage());
            $this->logEntryRepository->create(
                [
                    [
                        'message' => $e->getMessage(),
                        'level' => 500,
                        'channel' => 'cbax',
                        'createdAt' => date(Defaults::STORAGE_DATE_TIME_FORMAT, time()),
                        'context' => array('cbaxLexicon')
                    ],
                ],
                $context
            );
        }
    }
}
