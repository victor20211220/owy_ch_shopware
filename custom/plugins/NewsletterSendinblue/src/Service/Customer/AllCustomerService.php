<?php

namespace NewsletterSendinblue\Service\Customer;

use Monolog\Logger;
use NewsletterSendinblue\Controller\Api\GroupController;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class AllCustomerService
{
    /**
     * @var EntityRepository
     */
    private $customerRepository;

    /**
     * @var EntityRepository
     */
    private $newsletterRecipientRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param EntityRepository $customerRepository
     * @param EntityRepository $newsletterRecipientRepository
     * @param Logger $logger
     */
    public function __construct(
        EntityRepository $customerRepository,
        EntityRepository $newsletterRecipientRepository,
        Logger $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $requestParams
     * @param Context $context
     * @return EntityCollection
     */
    public function getAllCustomers(array $requestParams, Context $context): EntityCollection
    {
        $subscribed = $requestParams['subscribed'];
        $limit = $requestParams['limit'];
        $offset = $requestParams['offset'];
        $salesChannelId = $requestParams['salesChannelId'];
        $groupId = $requestParams['groupId'];

        [$recipientsEmails, $subscribedEmails, $unsubscribedEmails] = $this->getNewsletterRecipients($context);

        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('customer.active', 1));

        if ($subscribed) {
            if (!empty($recipientsEmails)) {
                $criteria->addFilter(
                    new NotFilter(
                        NotFilter::CONNECTION_AND,
                        [
                            new EqualsAnyFilter('customer.email', $recipientsEmails)
                        ]
                    )
                );
            }
        } else {
            $criteria->addFilter(new EqualsAnyFilter('customer.email', $unsubscribedEmails));
        }

        if (!empty($salesChannelId)) {
            $criteria->addFilter(new EqualsFilter('customer.salesChannelId', $salesChannelId));
        }

        //Are we dealing with a custom group id for the current list?
        if (!empty($groupId) && $groupId !== GroupController::GROUP_NEWSLETTER_RECIPIENT) {
            $criteria->addFilter(new EqualsFilter('customer.groupId', $groupId));
        }

        if ($offset) {
            if (!is_numeric($offset)) {
                $offset = (int)$offset;
            }
            $criteria->setOffset($offset);
        }

        if (!is_numeric($limit)) {
            $limit = (int)$limit;
        }

        $criteria->setLimit($limit);

        $criteria->addAssociations([
            'salutation',
            'language',
            'defaultPaymentMethod',
            'defaultBillingAddress',
            'defaultBillingAddress.country',
            'customFields',
            'salesChannel.orders'
        ]);

        return $this->customerRepository->search($criteria, $context)->getEntities();
    }

    /**
     * @param Context $context
     * @return array[]
     */
    private function getNewsletterRecipients(Context $context): array
    {
        $criteria = new Criteria();
        $newsletterRecipients = $this->newsletterRecipientRepository->search($criteria, $context);
        $unsubscribedEmails = [];
        $subscribedEmails = [];
        $recipientsEmails = [];
        /** @var NewsletterRecipientEntity $newsletterRecipient */
        foreach ($newsletterRecipients as $newsletterRecipient) {
            $recipientsEmails[] = $newsletterRecipient->getEmail();
            if ($newsletterRecipient->getStatus() === NewsletterSubscribeRoute::STATUS_OPT_OUT) {
                $unsubscribedEmails[] = $newsletterRecipient->getEmail();
                continue;
            }
            $subscribedEmails[] = $newsletterRecipient->getEmail();
        }

        return [$recipientsEmails, $subscribedEmails, $unsubscribedEmails];
    }
}
