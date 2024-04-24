<?php

namespace NewsletterSendinblue\Service\NewsletterRecipient;

use Monolog\Logger;
use NewsletterSendinblue\Controller\Api\CustomerFieldController;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;

class NewsletterRecipientPayloadCollector
{

    /**
     * @var CustomerFieldController
     */
    private $customerFieldController;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        CustomerFieldController $customerFieldController,
        Logger $logger
    )
    {
        $this->customerFieldController = $customerFieldController;
        $this->logger = $logger;
    }

    public function collectNewsletterRecipientUpdateData(NewsletterRecipientEntity $newsletterRecipientEntity): ?array
    {
        if (!$newsletterRecipientEntity) {
            return null;
        }

        $fields = $this->customerFieldController->getCustomerEntityFields('');
        $recipient = $this->customerFieldController->prepareNewsletterRecipients(
            [$newsletterRecipientEntity],
            $fields
        );

        if (empty($recipient)) {
            $this->logger->addRecord(Logger::ERROR, sprintf(
                    'error happened at preparing %s can for auto sync',
                    $newsletterRecipientEntity->getEmail()
                )
            );

            return null;
        }

        return reset($recipient);
    }

    public function collectNewsletterRecipientDeleteData(NewsletterRecipientEntity $newsletterRecipientEntity): ?array
    {
        if (!$newsletterRecipientEntity) {
            return null;
        }

        return [
            'email' => $newsletterRecipientEntity->getEmail()
        ];
    }
}
