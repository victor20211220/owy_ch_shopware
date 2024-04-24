<?php

namespace NewsletterSendinblue\Service\Cart;

use Monolog\Logger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SendinblueCartProcessorService implements CartProcessorInterface
{
    private $shouldCollectData = false;

    /**
     * @var CartEventProducer
     */
    private $cartEventProducer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * SendinblueCartProcessorService constructor.
     *
     * @param CartEventProducer $cartEventProducer
     * @param Logger $logger
     */
    public function __construct(CartEventProducer $cartEventProducer, Logger $logger)
    {
        $this->cartEventProducer = $cartEventProducer;
        $this->logger = $logger;
    }

    /**
     * @param CartDataCollection $data
     * @param Cart $original
     * @param Cart $toCalculate
     * @param SalesChannelContext $context
     * @param CartBehavior $behavior
     */
    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if (!$this->shouldCollectData) {
            return;
        }

        $this->cartEventProducer->processCart($toCalculate, $context);
        $this->setShouldCollectData(false);
    }

    /**
     * @param bool $shouldCollectData
     */
    public function setShouldCollectData(bool $shouldCollectData): void
    {
        $this->shouldCollectData = $shouldCollectData;
    }
}
