<?php

namespace NewsletterSendinblue\Service\Cart;

use Monolog\Logger;
use NewsletterSendinblue\Service\ApiClientService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartEventProducer
{
    const SIB_EVENT_CART_UPDATED = 'cart_updated';

    const SIB_EVENT_CART_DELETED = 'cart_deleted';

    const SIB_EVENT_ORDER_COMPLETED = 'order_completed';

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CartPayloadCollector
     */
    private $cartPayloadCollector;
    /**
     * @var ApiClientService
     */
    private $apiClient;
    /**
     * @var EntityRepository
     */
    private $newsletterRecipientRepository;

    /**
     * CartEventProducer constructor.
     *
     * @param CartPayloadCollector $cartPayloadCollector
     * @param ApiClientService $apiClient
     * @param EntityRepository $newsletterRecipientRepository
     * @param Logger $logger
     */
    public function __construct(
        CartPayloadCollector $cartPayloadCollector,
        ApiClientService     $apiClient,
        EntityRepository     $newsletterRecipientRepository,
        Logger               $logger
    )
    {
        $this->cartPayloadCollector = $cartPayloadCollector;
        $this->logger = $logger;
        $this->apiClient = $apiClient;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
    }

    /**
     * @param Cart $cart
     * @param SalesChannelContext $context
     */
    public function processCart(Cart $cart, SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();

        if (!$customer) {
            return;
        }

        try {
            $eventData = $this->cartPayloadCollector->collectBasePayload($customer);

            if ($cart->getLineItems()->count() > 0) {
                $eventName = static::SIB_EVENT_CART_UPDATED;
                $eventData += $this->cartPayloadCollector->collectCartPayload($cart, $context);
            } else {
                $eventName = static::SIB_EVENT_CART_DELETED;
            }
            $this->apiClient->setSalesChannelId($context->getSalesChannelId());
            $this->apiClient->trackEvent($eventName, $eventData);
        } catch (\Throwable $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * @param array $order
     * @param Cart $cart
     * @param SalesChannelContext $context
     */
    public function processOrder(array $order, Cart $cart, SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();

        if (!$customer) {
            return;
        }

        try {
            $eventData = $this->cartPayloadCollector->collectBasePayload($customer);
            $eventData += $this->cartPayloadCollector->collectOrderPayload($order, $cart, $context);

            $this->apiClient->setSalesChannelId($context->getSalesChannelId());
            $this->apiClient->trackEvent(static::SIB_EVENT_ORDER_COMPLETED, $eventData);
        } catch (\Throwable $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * @param OrderEntity $order
     * @param Context $context
     * @param string|null $salesChannelId
     * @return void
     */
    public function createTransactionalOrder(OrderEntity $order, Context $context, ?string $salesChannelId = null): void
    {
        $orderCustomer = $order->getOrderCustomer();
        if (!$orderCustomer || !$orderCustomer->getCustomer()) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $orderCustomer->getEmail()));
        $newsletterRecipient = $this->newsletterRecipientRepository->search($criteria, $context)->first();
        if (!$newsletterRecipient instanceof NewsletterRecipientEntity) {
            return;
        }

        try {
            $contact = $this->cartPayloadCollector->collectTransactionalOrderPayload($order, $orderCustomer->getEmail());
            $this->apiClient->setSalesChannelId($salesChannelId);
            $this->apiClient->createOrder($contact);
        } catch (\Throwable $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
        }
    }
}
