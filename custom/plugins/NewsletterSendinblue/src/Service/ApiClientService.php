<?php

namespace NewsletterSendinblue\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Monolog\Logger;

class ApiClientService
{

    private const USER_AGENT = 'sendinblue_plugins/shopware6';

    private const SIB_TRACK_EVENT_URI = 'https://in-automate.brevo.com/api/v2/trackEvent';

    private const SIB_INTEGRATIONS_CONTACT_CREATED_URI = '/integrations/api/events/%s/contact_created';

    private const SIB_INTEGRATIONS_CONTACT_UPDATED_URI = '/integrations/api/events/%s/contact_updated';

    private const SIB_INTEGRATIONS_CONTACT_DELETED_URI = '/integrations/api/events/%s/contact_deleted';

    private const SIB_INTEGRATIONS_ORDER_CREATED_URI = '/integrations/api/events/%s/order_created';

    private const SIB_INTEGRATIONS_DISABLE_SMTP_URI = '/integrations/api/events/%s/disable_smtp';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var VersionProvider
     */
    private $versionProvider;

    /**
     * ApiClientService constructor.
     *
     * @param ConfigService $configService
     * @param Logger $logger
     */
    public function __construct(ConfigService $configService, Logger $logger, VersionProvider $versionProvider)
    {
        $this->configService = $configService;
        $this->logger = $logger;
        $this->versionProvider = $versionProvider;
    }

    /**
     * @param string|null $salesChannelId
     */
    public function setSalesChannelId(?string $salesChannelId = null)
    {
        $this->configService->setSalesChannelId($salesChannelId);
    }

    /**
     * @return ClientInterface
     */
    private function getHttpClient(): ClientInterface
    {
        if ($this->httpClient == null) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    /**
     * @param array $contact
     */
    public function createContact(array $contact): void
    {
        $this->sendRequest('POST', $this->getConnectionUri(self::SIB_INTEGRATIONS_CONTACT_CREATED_URI), $contact);
    }

    /**
     * @param array $contact
     */
    public function updateContact(array $contact): void
    {
        $this->sendRequest('POST', $this->getConnectionUri(self::SIB_INTEGRATIONS_CONTACT_UPDATED_URI), $contact);
    }

    /**
     * @param array $contact
     */
    public function deleteContact(array $contact): void
    {
        $this->sendRequest('POST', $this->getConnectionUri(self::SIB_INTEGRATIONS_CONTACT_DELETED_URI), $contact);
    }

    public function disableSmtp(array $data): void
    {
        $this->sendRequest('POST', $this->getConnectionUri(self::SIB_INTEGRATIONS_DISABLE_SMTP_URI), $data);
    }

    public function createOrder(array $contact): void
    {
        $this->sendRequest('POST', $this->getConnectionUri(self::SIB_INTEGRATIONS_ORDER_CREATED_URI), $contact);
    }

    /**
     * @param string $eventName
     * @param array $eventData
     */
    public function trackEvent(string $eventName, array $eventData): void
    {
        $this->sendRequest(
            'POST',
            self::SIB_TRACK_EVENT_URI,
            $eventData + ['event' => $eventName],
            ['ma-key' => $this->configService->getMAKey(),
            'api-key' => $this->configService->getMAKey()]
        );
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     */
    private function sendRequest(string $method, string $url, array $data = [], array $headers = []): void
    {
        $headers['User-Agent'] = self::USER_AGENT;
        $headers['x-sib-shop-version'] = $this->versionProvider->getFormattedShopwareVersion();
        $headers['x-sib-plugin-version'] = $this->versionProvider->getPluginVersion();

        try {
            $this->getHttpClient()->request(
                $method,
                $url,
                [
                    RequestOptions::HEADERS => $headers,
                    RequestOptions::JSON => $data
                ]
            );
        } catch (GuzzleException $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getConnectionUri(string $path): string
    {
        return $this->configService->getSendinblueBaseUrl() . sprintf($path, $this->configService->getUserConnectionId());
    }
}
