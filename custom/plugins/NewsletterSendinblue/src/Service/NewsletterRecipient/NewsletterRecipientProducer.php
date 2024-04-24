<?php

namespace NewsletterSendinblue\Service\NewsletterRecipient;

use Monolog\Logger;
use NewsletterSendinblue\Service\ApiClientService;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context as ContextAlias;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class NewsletterRecipientProducer
{
    /**
     * @var ApiClientService
     */
    private $apiClient;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @var NewsletterRecipientPayloadCollector
     */
    private $payloadCollector;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * NewsletterRecipientProducer constructor.
     *
     * @param ApiClientService $apiClient
     * @param EntityRepository $entityRepository
     * @param NewsletterRecipientPayloadCollector $payloadCollector
     * @param Logger $logger
     */
    public function __construct(
        ApiClientService $apiClient,
        EntityRepository $entityRepository,
        NewsletterRecipientPayloadCollector $payloadCollector,
        Logger $logger
    ) {
        $this->apiClient = $apiClient;
        $this->entityRepository = $entityRepository;
        $this->payloadCollector = $payloadCollector;
        $this->logger = $logger;
    }

    /**
     * @param NewsletterRecipientEntity $newsletterRecipientEntity
     * @param string|null $salesChannelId
     */
    public function confirmContact(NewsletterRecipientEntity $newsletterRecipientEntity, ?string $salesChannelId = null): void
    {
        try {
            $this->apiClient->setSalesChannelId($salesChannelId);
            $this->apiClient->createContact(
                $this->payloadCollector->collectNewsletterRecipientUpdateData(
                    $this->getNewsletterRecipientEntity(['id' => $newsletterRecipientEntity->getId()])
                )
            );
        } catch(\Exception $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * @param string $newsletterRecipientId
     * @param string|null $salesChannelId
     */
    public function updateContact(string $newsletterRecipientId, ?string $salesChannelId = null): void
    {
        try {
            $newsletterRecipient = $this->getNewsletterRecipientEntity(['id' => $newsletterRecipientId]);
            if (empty($newsletterRecipient) || $newsletterRecipient->getStatus() === NewsletterSubscribeRoute::STATUS_NOT_SET) {
                return;
            }
            $this->apiClient->setSalesChannelId($salesChannelId);
            $this->apiClient->updateContact(
                $this->payloadCollector->collectNewsletterRecipientUpdateData($newsletterRecipient)
            );
        } catch (\Exception $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * @param string $newsletterRecipientId
     * @param string|null $salesChannelId
     */
    public function unsubscribeContact(string $newsletterRecipientId, ?string $salesChannelId = null): void
    {
        try {
            $this->apiClient->setSalesChannelId($salesChannelId);
            $this->apiClient->deleteContact(
                $this->payloadCollector->collectNewsletterRecipientDeleteData(
                    $this->getNewsletterRecipientEntity(['id' => $newsletterRecipientId])
                )
            );
        } catch (\Exception $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * @param array $newsletterRecipientParams
     *
     * @return NewsletterRecipientEntity|null
     */
    private function getNewsletterRecipientEntity(array $newsletterRecipientParams): ?NewsletterRecipientEntity
    {
        try {
            $criteria = new Criteria();

            foreach ($newsletterRecipientParams as $field => $value) {
                $criteria->addFilter(new EqualsFilter($field, $value));
            }

            $criteria->addAssociations([
                'salesChannel',
                'salutation',
                'language',
                'salesChannel.customerGroup',
            ]);

            /** @var NewsletterRecipientEntity[]|array */
            return $this->entityRepository->search(
                $criteria,
                ContextAlias::createDefaultContext()
            )->first();
        } catch (InconsistentCriteriaIdsException $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());

            return null;
        }
    }
}
