<?php

namespace NewsletterSendinblue\Service\Customer;

use Monolog\Logger;
use NewsletterSendinblue\Service\ApiClientService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context as ContextAlias;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CustomerProducer
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
     * @var CustomerPayloadCollector
     */
    private $payloadCollector;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CustomerProducer constructor.
     *
     * @param ApiClientService $apiClient
     * @param EntityRepository $entityRepository
     * @param CustomerPayloadCollector $payloadCollector
     * @param Logger $logger
     */
    public function __construct(
        ApiClientService $apiClient,
        EntityRepository $entityRepository,
        CustomerPayloadCollector $payloadCollector,
        Logger $logger
    ) {
        $this->apiClient = $apiClient;
        $this->entityRepository = $entityRepository;
        $this->payloadCollector = $payloadCollector;
        $this->logger = $logger;
    }

    /**
     * @param CustomerEntity $customerEntity
     * @param string|null $salesChannelId
     * @return void
     */
    public function confirmContact(CustomerEntity $customerEntity, ?string $salesChannelId = null): void
    {
        try {
            $this->apiClient->setSalesChannelId($salesChannelId);
            $this->apiClient->createContact(
                $this->payloadCollector->collectCustomerUpdateData(
                    $this->getCustomerEntity(['id' => $customerEntity->getId()])
                )
            );
        } catch(\Exception $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * @param array $customerParams
     * @return CustomerEntity|null
     */
    private function getCustomerEntity(array $customerParams): ?CustomerEntity
    {
        try {
            $criteria = new Criteria();

            foreach ($customerParams as $field => $value) {
                $criteria->addFilter(new EqualsFilter($field, $value));
            }

            $criteria->addAssociations([
                'salesChannel',
                'salutation',
                'language',
                'salesChannel.customerGroup',
            ]);

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
