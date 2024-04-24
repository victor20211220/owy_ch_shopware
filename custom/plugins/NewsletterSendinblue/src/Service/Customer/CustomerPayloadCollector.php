<?php

namespace NewsletterSendinblue\Service\Customer;

use Monolog\Logger;
use NewsletterSendinblue\Controller\Api\CustomerFieldController;
use Shopware\Core\Checkout\Customer\CustomerEntity;

class CustomerPayloadCollector
{
    /**
     * @var CustomerFieldController
     */
    private $customerFieldController;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param CustomerFieldController $customerFieldController
     * @param Logger $logger
     */
    public function __construct(
        CustomerFieldController $customerFieldController,
        Logger $logger
    )
    {
        $this->customerFieldController = $customerFieldController;
        $this->logger = $logger;
    }

    /**
     * @param CustomerEntity|null $customerEntity
     * @return array|null
     */
    public function collectCustomerUpdateData(?CustomerEntity $customerEntity): ?array
    {
        if (!$customerEntity) {
            return null;
        }

        $fields = $this->customerFieldController->getCustomerEntityFields('');
        $recipient = $this->customerFieldController->prepareNewsletterRecipients(
            [$customerEntity],
            $fields
        );

        if (empty($recipient)) {
            $this->logger->addRecord(Logger::ERROR, sprintf(
                    'error happened at preparing %s can for auto sync',
                    $customerEntity->getEmail()
                )
            );

            return null;
        }

        return reset($recipient);
    }
}
