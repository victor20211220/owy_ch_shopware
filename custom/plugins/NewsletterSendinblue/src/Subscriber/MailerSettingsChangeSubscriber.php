<?php

namespace NewsletterSendinblue\Subscriber;

use Exception;
use Monolog\Logger;
use NewsletterSendinblue\Service\ApiClientService;
use NewsletterSendinblue\Service\ConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailerSettingsChangeSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var ApiClientService
     */
    private $apiClientService;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        ConfigService    $configService,
        ApiClientService $apiClientService,
        Logger           $logger
    )
    {
        $this->configService = $configService;
        $this->apiClientService = $apiClientService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'onMailerSettingsChange'
        ];
    }

    public function onMailerSettingsChange(EntityWrittenContainerEvent $event): void
    {
        try {
            $entityWrittenEvent = $event->getEventByEntityName('system_config');
            if (!$entityWrittenEvent) {
                return;
            }

            foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
                if ($writeResult->getOperation() !== 'update') {
                    continue;
                }
                $payload = $writeResult->getPayload();
                if (empty($payload['configurationKey'])) {
                    return;
                }
                $checkConnection = !($payload['configurationKey'] === ConfigService::CONFIG_USER_CONNECTION_ID);
                $this->configService->setSalesChannelId($payload['salesChannelId'] ?? null, $checkConnection);
                if (!$this->configService->isSmtpEnabled() || !$this->configService->getSmtpHost()) {
                    continue;
                }
                if (($payload['configurationKey'] == ConfigService::CORE_MAILER_HOST_CONFIG
                        && $payload['configurationValue'] !== $this->configService->getSmtpHost())
                    || ($payload['configurationKey'] == ConfigService::CORE_MAILER_AGENT_CONFIG
                        && $payload['configurationValue'] !== ConfigService::CORE_MAILER_AGENT_VALUE)
                ) {
                    $this->apiClientService->disableSmtp([ConfigService::CONFIG_API_KEY => $this->configService->getApiKey()]);
                    $this->configService->setIsSmtpEnabled(false);
                }
            }
        } catch (Exception $e) {
            $this->logger->addRecord(
                Logger::ERROR,
                sprintf('onMailerSettingsChange Error: %s', $e->getMessage())
            );
        }
    }
}
