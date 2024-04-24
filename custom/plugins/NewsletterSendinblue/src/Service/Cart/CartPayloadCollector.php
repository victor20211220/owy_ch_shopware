<?php

namespace NewsletterSendinblue\Service\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Shopware\Core\Framework\Context as ContextAlias;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartPayloadCollector
{
    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var SeoUrlPlaceholderHandlerInterface
     */
    private $seoUrlPlaceholderHandler;

    /**
     * CartPayloadCollector constructor.
     *
     * @param EntityRepository $entityRepository
     * @param UrlGeneratorInterface $router
     * @param SeoUrlPlaceholderHandlerInterface $seoUrlPlaceholderHandler
     */
    public function __construct(
        EntityRepository $entityRepository,
        UrlGeneratorInterface $router,
        SeoUrlPlaceholderHandlerInterface $seoUrlPlaceholderHandler
    ) {
        $this->entityRepository = $entityRepository;
        $this->router = $router;
        $this->seoUrlPlaceholderHandler = $seoUrlPlaceholderHandler;
    }

    /**
     * @param CustomerEntity $customer
     *
     * @return array
     */
    public function collectBasePayload(CustomerEntity $customer): array
    {
        return [
            'email' => $customer->getEmail(),
            'properties' => $this->getCustomerProperties($customer)
        ];
    }

    /**
     * @param Cart $cart
     * @param SalesChannelContext $context
     *
     * @return array
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function collectCartPayload(Cart $cart, SalesChannelContext $context): array
    {
        return ['eventdata' => $this->getEventData($context, $cart)];
    }

    /**
     * @param array $order
     * @param Cart $cart
     * @param SalesChannelContext $context
     *
     * @return array
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function collectOrderPayload(array $order, Cart $cart, SalesChannelContext $context): array
    {
        $eventData = $this->getEventData($context, $cart);
        $data = &$eventData['data'];

        $data['date'] = date("m-d-Y", strtotime($order['orderDateTime']));
        $data['id'] = $order['orderNumber'];
        $data['revenue'] = $cart->getPrice()->getTotalPrice();

        $customer = $context->getCustomer();

        $billingAddress = $customer->getActiveBillingAddress();
        $shippingAddress = $customer->getActiveShippingAddress();

        $data['miscellaneous'] = $this->getMiscellaneous($context, $customer);
        $data['billing_address'] = $this->getCustomerAddress($billingAddress);
        $data['shipping_address'] = $this->getCustomerAddress($shippingAddress);

        return ['eventdata' => $eventData];
    }

    /**
     * @param CustomerEntity $customer
     *
     * @return array
     */
    private function getCustomerProperties(CustomerEntity $customer): array
    {
        return [
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstName(),
            'lastname' => $customer->getLastName()
        ];
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @param Cart $cart
     *
     * @return array
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function getEventData(SalesChannelContext $salesChannelContext, Cart $cart): array
    {
        $shippingTotalPrice = $cart->getShippingCosts()->getTotalPrice();
        $shippingTaxAmount = $cart->getShippingCosts()->getCalculatedTaxes()->getAmount();

        $totalPrice = $cart->getPrice()->getTotalPrice();
        $taxAmount = $cart->getPrice()->getCalculatedTaxes()->getAmount();

        $discount = $discountTaxAmount = 0;
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() == 'promotion') {
                $discount = round(abs($lineItem->getPrice()->getTotalPrice()), 2);
                $discountTaxAmount = round(abs($lineItem->getPrice()->getCalculatedTaxes()->getAmount()), 2);
            }
        }

        $salesChannel = $salesChannelContext->getSalesChannel();

        return [
            'id' => md5($cart->getToken()),
            'data' => [
                'affiliation' => $salesChannel->getName(),
                'currency' => $salesChannel->getCurrency()->getShortName(),
                'discount' => $discount - $discountTaxAmount,
                'discount_taxinc' => $discount,
                'shipping' => $shippingTotalPrice - $shippingTaxAmount,
                'shipping_taxinc' => $shippingTotalPrice,
                'subtotal' => $totalPrice - $taxAmount,
                'subtotal_predisc' => $totalPrice - $taxAmount + $discount,
                'subtotal_predisc_taxinc' => $totalPrice + $discount,
                'subtotal_taxinc' => $totalPrice,
                'tax' => $taxAmount,
                'total' => $totalPrice,
                'total_before_tax' => $totalPrice - $taxAmount,
                'url' => $this->router->generate('frontend.checkout.cart.page', [], Router::ABSOLUTE_URL),
                'items' => $this->getCartItems($cart->getLineItems(), $salesChannelContext)
            ]
        ];
    }

    /**
     * @param LineItemCollection $lineItems
     * @param SalesChannelContext $salesChannelContext
     *
     * @return array
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function getCartItems(LineItemCollection $lineItems, SalesChannelContext $salesChannelContext): array
    {
        $cartItems = [];

        $productEntities = $this->getProductEntities(
            $this->getLineItemIds($lineItems)
        );

        foreach ($lineItems->getElements() as $lineItem) {
            if ($lineItem->getType() != 'product') {
                continue;
            }

            $totalPrice = $lineItem->getPrice()->getTotalPrice();

            /** @var CalculatedTax[] */
            $calculatedTaxElements = $lineItem->getPrice()->getCalculatedTaxes()->getElements();

            /** @var CalculatedTax */
            $calculatedTax = array_shift($calculatedTaxElements);
            $taxPrice = $calculatedTax->getTax();
            $taxRate = $calculatedTax->getTaxRate();

            $productEntity = $productEntities[$lineItem->getId()];

            $variant = $this->getProductVariant($lineItem->getPayload());

            $productUrl = $this->seoUrlPlaceholderHandler->replace(
                $this->seoUrlPlaceholderHandler->generate(
                    'frontend.detail.page',
                    ['productId' => $productEntity->getId()]
                ),
                $salesChannelContext->getSalesChannel()->getDomains()->first()->getUrl(),
                $salesChannelContext
            );

            $productParent = $productEntity->getParent();

            $productDescription = $productEntity->getDescription() ?: $productParent->getDescription();

            $width = $productEntity->getWidth() ?: ($productParent ? $productParent->getWidth() : '');
            $height = $productEntity->getHeight() ?: ($productParent ? $productParent->getHeight() : '');
            $length = $productEntity->getLength() ?: ($productParent ? $productParent->getLength() : '');

            $cartItems[]  = [
                'available_now' => $productEntity->getAvailable(),
                'category' => implode(', ', $this->getCategoriesByProduct($productEntity)),
                'description_short' => $productDescription,
                'disc_amount' => '',
                'disc_amount_taxinc' => '',
                'disc_rate' => '',
                'id' => $lineItem->getId(),
                'image' => $lineItem->getCover() ? $lineItem->getCover()->getUrl() : '',
                'name' => $lineItem->getLabel(),
                'price' => $totalPrice - $taxPrice,
                'price_predisc' => $totalPrice - $taxPrice,
                'price_predisc_taxinc' => $totalPrice,
                'price_taxinc' => $totalPrice,
                'quantity' => $lineItem->getQuantity(),
                'size' => implode ('x', array_filter([
                    $width,
                    $height,
                    $length
                ])),
                'sku' => $productEntity->getProductNumber(),
                'tax_amount' => $taxPrice,
                'tax_name' => sprintf('%s%%', intval($taxRate)),
                'tax_rate' => $taxRate,
                'url' => $productUrl,
                'variant_id' => $variant ? $variant['id'] : '',
                'variant_id_name' => $variant ? $variant['name'] : '',
            ];
        }

        return $cartItems;
    }

    /**
     * @param LineItemCollection $lineItems
     *
     * @return array
     */
    private function getLineItemIds(LineItemCollection $lineItems): array
    {
        $productIds = [];

        foreach ($lineItems->getElements() as $lineItem) {
            $productIds[] = $lineItem->getId();
        }

        return $productIds;
    }

    /**
     * @param array $productIds
     *
     * @return ProductEntity[]|array
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function getProductEntities(array $productIds): array
    {
        /** @var ProductEntity[]|array $productEntities */
        $productEntities = $this->getProductEntitiesByIds($productIds);

        $parentIds = $this->getParentIds($productEntities);

        if (!empty($parentIds)) {
            /** @var ProductEntity[]|array $parents */
            $parentEntities = $this->getProductEntitiesByIds($parentIds);

            foreach ($parentEntities as $parentEntity) {
                $parentId = $parentEntity->getId();

                foreach ($productEntities as $productEntity) {
                    if ($parentId === $productEntity->getParentId()) {
                        $productEntity->setParent($parentEntity);
                    }
                }
            }
        }

        return $productEntities;
    }

    /**
     * @param ProductEntity[]|array $productEntities
     *
     * @return array
     */
    private function getParentIds(array $productEntities): array
    {
        $parentIds = [];

        foreach ($productEntities as $productEntity) {
            $parentId = $productEntity->getParentId();
            if (!empty($parentId)) {
                $parentIds[$productEntity->getId()] = $parentId;
            }
        }

        return $parentIds;
    }

    /**
     * @param array $ids
     *
     * @return ProductEntity[]|array
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function getProductEntitiesByIds(array $ids): array
    {
        $criteria = new Criteria($ids);
        $criteria->addAssociations(['categories', static::class]);

        /** @var ProductEntity[]|array $elements */
        return $this->entityRepository->search(
            $criteria,
            ContextAlias::createDefaultContext()
        )->getElements();
    }

    /**
     * @param array $payload
     *
     * @return array
     */
    private function getProductVariant(array $payload): array
    {
        $variant = [];

        if (!empty($payload['options'])) {
            $variant['id'] = $payload['productNumber'];

            foreach ($payload['options'] as $option) {
                $variant['name'] = sprintf('%s: %s', $option['group'], $option['option']);
            }
        }

        return $variant;
    }

    /**
     * @param ProductEntity $productEntity
     *
     * @return array
     */
    private function getCategoriesByProduct(ProductEntity $productEntity): array
    {
        $categories = [];

        $categoryCollection = $productEntity->getCategories()->getElements()
            ? $productEntity->getCategories()
            : $productEntity->getParent()->getCategories();

        if ($categoryCollection) {
            foreach ($categoryCollection->getElements() as $categoryEntity) {
                $categories[] = $categoryEntity->getName();
            }
        }

        return $categories;
    }

    /**
     * @param SalesChannelContext $context
     * @param CustomerEntity $customer
     *
     * @return array
     */
    private function getMiscellaneous(SalesChannelContext $context, CustomerEntity $customer): array
    {
        return [
            'cart_DISCOUNT' => '',
            'cart_DISCOUNT_TAX' => '',
            'customer_IP_ADDRESS' => '',
            'customer_USER' => '',
            'customer_USER_AGENT' => '',
            'payment_METHOD' => $context->getPaymentMethod() ? $context->getPaymentMethod()->getName() : '',
            'payment_METHOD_TITLE' => $context->getPaymentMethod() ? $context->getPaymentMethod()->getFormattedHandlerIdentifier() : '',
            'refunded_AMOUNT' => '',
            'user_LOGIN' => $customer->getEmail(),
            'user_PASSWORD' => '',
        ];
    }

    /**
     * @param CustomerAddressEntity $address
     *
     * @return array
     */
    private function getCustomerAddress(CustomerAddressEntity $address): array
    {
        return [
            'address1' => $address->getStreet() ?: '',
            'address2' => $address->getAdditionalAddressLine1() ?: '',
            'city' => $address->getCity() ?: '',
            'company' => $address->getCompany() ?: '',
            'country' => $address->getCountry()->getName() ?: '',
            'firstname' => $address->getFirstName() ?: '',
            'lastname' => $address->getLastName() ?: '',
            'phone' => $address->getPhoneNumber() ?: '',
            'state' => $address->getCountryState() ?: '',
            'zipcode' => $address->getZipcode() ?: '',
        ];
    }

    public function collectTransactionalOrderPayload(OrderEntity $order, string $userEmail): array
    {
        return [
            "email" => $userEmail,
            "orderId" => $order->getOrderNumber(),
            "orderDate" => $order->getOrderDate()->format('Y-m-d'),
            "orderPrice" => $order->getAmountTotal()
        ];
    }
}
