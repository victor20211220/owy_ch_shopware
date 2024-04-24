<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;

final class OrderModule extends BaseModule
{
    private array $orderIdCache = [];
    private array $orderCustomerIdCache = [];
    private array $orderLineItemIdCache = [];
    private array $orderTransactionIdCache = [];
    private array $orderDeliveryIdCache = [];
    private array $orderDeliveryPositionIdCache = [];

    public function selectOrderId(string $orderNumber): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->orderIdCache[$orderNumber])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM `order` WHERE order_number = :number
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('number', $orderNumber, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->orderIdCache[$orderNumber] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->orderIdCache[$orderNumber] : (string) $result;
    }

    public function selectOrderBillingAddressId(string $orderId): ?string
    {
        $statement = <<<'SQL'
            SELECT HEX(billing_address_id) FROM `order` WHERE id = UNHEX(:orderId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function selectLatestAddressId(string $orderId): ?string
    {
        $statement = <<<'SQL'
            SELECT HEX(id) FROM `order_address` WHERE order_id = UNHEX(:orderId) ORDER BY created_at DESC
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function selectOrderCustomerId(string $orderId, string $customerId): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->orderCustomerIdCache[$orderId])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM `order_customer` WHERE order_id = UNHEX(:orderId) AND customer_id = UNHEX(:customerId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('customerId', $customerId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->orderCustomerIdCache[$orderId] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->orderCustomerIdCache[$orderId] : (string) $result;
    }

    public function selectOrderTransactionId(string $orderId, string $paymentMethodId): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->orderTransactionIdCache[$orderId . $paymentMethodId])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM `order_transaction` WHERE order_id = UNHEX(:orderId) AND payment_method_id = UNHEX(:paymentMethodId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('paymentMethodId', $paymentMethodId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->orderTransactionIdCache[$orderId . $paymentMethodId] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->orderTransactionIdCache[$orderId . $paymentMethodId] : (string) $result;
    }

    public function selectOrderDeliveryId(string $orderId): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->orderDeliveryIdCache[$orderId])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM `order_delivery` WHERE order_id = UNHEX(:orderId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->orderDeliveryIdCache[$orderId] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->orderDeliveryIdCache[$orderId] : (string) $result;
    }

    public function selectOrderDeliveryPositionId(string $deliveryId, string $lineItemId): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->orderDeliveryPositionIdCache[$deliveryId . $lineItemId])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM `order_delivery_position` WHERE order_delivery_id = UNHEX(:deliveryId) AND order_line_item_id = UNHEX(:lineItemId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('deliveryId', $deliveryId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('lineItemId', $lineItemId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->orderDeliveryPositionIdCache[$deliveryId . $lineItemId] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->orderDeliveryPositionIdCache[$deliveryId . $lineItemId] : (string) $result;
    }

    public function selectOrderLineItemId(string $orderId, string $productId, int $position): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->orderLineItemIdCache[$orderId . $productId])) {
            $statement = <<<'SQL'
                SELECT HEX(id)
                FROM `order_line_item`
                WHERE order_id = UNHEX(:orderId)
                AND product_id = UNHEX(:productId)
                AND position = :position
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('position', $position, \PDO::PARAM_INT);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->orderLineItemIdCache[$orderId . $productId] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->orderLineItemIdCache[$orderId . $productId] : (string) $result;
    }

    public function storeOrder(
        string $id,
        string $stateId,
        string $orderNumber,
        string $currencyId,
        string $languageId,
        float $currencyFactor,
        string $salesChannelId,
        string $billingAddressId,
        CartPrice $cartPrice,
        \DateTimeInterface $orderDateTime,
        CalculatedPrice $calculatedPrice,
        ?array $customFields = null,
        ?string $deepLinkCode = null,
        ?string $affiliateCode = null,
        ?string $campaignCode = null,
        ?CashRoundingConfig $itemRounding = null,
        ?CashRoundingConfig $totalRounding = null,
        ?array $ruleIds = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO `order` (id, version_id, state_id, order_number, currency_id, language_id, currency_factor, sales_channel_id, billing_address_id, billing_address_version_id, price, order_date_time, shipping_costs, deep_link_code, custom_fields, affiliate_code, campaign_code, created_at, item_rounding, total_rounding, rule_ids)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:stateId), :orderNumber, UNHEX(:currencyId), UNHEX(:languageId), :currencyFactor, UNHEX(:salesChannelId), UNHEX(:billingId), UNHEX(:versionId), :price, :orderTime, :shippingCosts, :deepLinkCode, :customFields, :affiliateCode, :campaignCode, NOW(), :itemRounding, :totalRounding, :ruleIds)
            ON DUPLICATE KEY UPDATE state_id = UNHEX(:stateId),
                                    order_number = :orderNumber,
                                    currency_id = UNHEX(:currencyId),
                                    language_id = UNHEX(:languageId),
                                    currency_factor = :currencyFactor,
                                    sales_channel_id = UNHEX(:salesChannelId),
                                    billing_address_id = UNHEX(:billingId),
                                    price = :price,
                                    order_date_time = :orderTime,
                                    shipping_costs = :shippingCosts,
                                    deep_link_code = :deepLinkCode,
                                    custom_fields = :customFields,
                                    affiliate_code = :affiliateCode,
                                    campaign_code = :campaignCode,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderNumber', $orderNumber, \PDO::PARAM_STR);
        $preparedStatement->bindValue('deepLinkCode', $deepLinkCode, \PDO::PARAM_STR);
        $preparedStatement->bindValue('campaignCode', $campaignCode, \PDO::PARAM_STR);
        $preparedStatement->bindValue('affiliateCode', $affiliateCode, \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderTime', $orderDateTime->format('Y-m-d H:i:s.u'), \PDO::PARAM_STR);
        $preparedStatement->bindValue('stateId', $stateId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('currencyId', $currencyId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('billingId', $billingAddressId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('currencyFactor', $currencyFactor, \PDO::PARAM_STR);
        $preparedStatement->bindValue('price', json_encode($cartPrice, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue('shippingCosts', json_encode($calculatedPrice, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->bindValue(
            'itemRounding',
            null === $itemRounding
            ? null
            : json_encode($itemRounding, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->bindValue(
            'totalRounding',
            null === $totalRounding
            ? null
            : json_encode($totalRounding, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->bindValue(
            'ruleIds',
            null === $ruleIds
            ? null
            : json_encode($ruleIds, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function storeOrderLineItem(
        string $id,
        string $orderId,
        string $productId,
        string $type,
        string $label,
        int $quantity,
        array $payload,
        CalculatedPrice $calculatedPrice,
        ?PriceDefinitionInterface $priceDefinition = null,
        ?string $description = null,
        ?string $coverId = null,
        ?string $parentId = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO `order_line_item` (id, version_id, order_id, order_version_id, parent_id, parent_version_id, identifier, referenced_id, product_id, product_version_id, label, description, cover_id, quantity, type, payload, price_definition, price, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:orderId), UNHEX(:versionId), UNHEX(:parentId), UNHEX(:versionId), :productId, :productId, UNHEX(:productId), UNHEX(:versionId), :label, :description, UNHEX(:coverId), :quantity, :type, :payload, :priceDefinition, :price, :customFields, NOW())
            ON DUPLICATE KEY UPDATE label = :label,
                                    description = :description,
                                    cover_id = UNHEX(:coverId),
                                    quantity = :quantity,
                                    type = :type,
                                    payload = :payload,
                                    price_definition = :priceDefinition,
                                    price = :price,
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('label', $label, \PDO::PARAM_STR);
        $preparedStatement->bindValue('description', $description, \PDO::PARAM_STR);
        $preparedStatement->bindValue('type', $type, \PDO::PARAM_STR);
        $preparedStatement->bindValue('quantity', $quantity, \PDO::PARAM_INT);
        $preparedStatement->bindValue('coverId', $coverId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('parentId', $parentId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('productId', $productId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('payload', json_encode($payload, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue('price', json_encode($calculatedPrice, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'priceDefinition',
            null === $priceDefinition
            ? null
            : json_encode($priceDefinition, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function storeOrderAddress(
        string $id,
        string $orderId,
        string $countryId,
        string $salutationId,
        string $firstName,
        string $lastName,
        string $street,
        string $city,
        string $zipCode,
        ?string $phoneNumber = null,
        ?string $additionalOne = null,
        ?string $additionalTwo = null,
        ?string $vatId = null,
        ?string $company = null,
        ?string $department = null,
        ?string $title = null,
        ?string $countryStateId = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO `order_address` (id, version_id, country_id, country_state_id, order_id, order_version_id, company, department, salutation_id, title, first_name, last_name, street, zipcode, city, vat_id, phone_number, additional_address_line1, additional_address_line2, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:countryId), UNHEX(:countryStateId), UNHEX(:orderId), UNHEX(:versionId), :company, :department, UNHEX(:salutationId), :title, :firstName, :lastName, :street, :zipCode, :city, :vatId, :phoneNumber, :additionalOne, :additionalTwo, :customFields, NOW())
            ON DUPLICATE KEY UPDATE country_id = UNHEX(:countryId),
                                    country_state_id = UNHEX(:countryStateId),
                                    company = :company,
                                    department = :department,
                                    salutation_id = UNHEX(:salutationId),
                                    title = :title,
                                    first_name = :firstName,
                                    last_name = :lastName,
                                    street = :street,
                                    zipcode = :zipCode,
                                    city = :city,
                                    vat_id = :vatId,
                                    phone_number = :phoneNumber,
                                    additional_address_line1 = :additionalOne,
                                    additional_address_line2 = :additionalTwo,
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salutationId', $salutationId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('countryId', $countryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('countryStateId', $countryStateId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('company', $company, \PDO::PARAM_STR);
        $preparedStatement->bindValue('department', $department, \PDO::PARAM_STR);
        $preparedStatement->bindValue('title', $title, \PDO::PARAM_STR);
        $preparedStatement->bindValue('firstName', $firstName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('lastName', $lastName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('additionalOne', $additionalOne, \PDO::PARAM_STR);
        $preparedStatement->bindValue('additionalTwo', $additionalTwo, \PDO::PARAM_STR);
        $preparedStatement->bindValue('street', $street, \PDO::PARAM_STR);
        $preparedStatement->bindValue('city', $city, \PDO::PARAM_STR);
        $preparedStatement->bindValue('zipCode', $zipCode, \PDO::PARAM_STR);
        $preparedStatement->bindValue('phoneNumber', $phoneNumber, \PDO::PARAM_STR);
        $preparedStatement->bindValue('vatId', $vatId, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function storeOrderCustomer(
        string $id,
        string $orderId,
        string $customerId,
        string $salutationId,
        string $firstName,
        string $lastName,
        string $email,
        string $customerNumber,
        ?string $title,
        ?string $company,
        ?string $remoteAddress = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO `order_customer` (id, version_id, customer_id, order_id, order_version_id, email, salutation_id, first_name, last_name, title, company, customer_number, custom_fields, created_at, remote_address)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:customerId), UNHEX(:orderId), UNHEX(:versionId), :email, UNHEX(:salutationId), :firstName, :lastName, :title, :company, :customerNumber, :customFields, NOW(), :remoteAddress)
            ON DUPLICATE KEY UPDATE company = :company,
                                    salutation_id = UNHEX(:salutationId),
                                    title = :title,
                                    first_name = :firstName,
                                    last_name = :lastName,
                                    email = :email,
                                    customer_number = :customerNumber,
                                    remote_address = :remoteAddress,
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salutationId', $salutationId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('firstName', $firstName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('lastName', $lastName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('title', $title, \PDO::PARAM_STR);
        $preparedStatement->bindValue('company', $company, \PDO::PARAM_STR);
        $preparedStatement->bindValue('customerNumber', $customerNumber, \PDO::PARAM_STR);
        $preparedStatement->bindValue('remoteAddress', $remoteAddress, \PDO::PARAM_STR);
        $preparedStatement->bindValue('customerId', $customerId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('email', $email, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function storeOrderTransaction(
        string $id,
        string $orderId,
        string $stateId,
        string $paymentMethodId,
        CalculatedPrice $calculatedPrice,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO `order_transaction` (id, version_id, order_id, order_version_id, state_id, payment_method_id, amount, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:orderId), UNHEX(:versionId), UNHEX(:stateId), UNHEX(:paymentMethodId), :amount, :customFields, NOW())
            ON DUPLICATE KEY UPDATE amount = :amount,
                                    state_id = UNHEX(:stateId),
                                    payment_method_id = UNHEX(:paymentMethodId),
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('stateId', $stateId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('paymentMethodId', $paymentMethodId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('amount', json_encode($calculatedPrice, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function storeOrderDelivery(
        string $id,
        string $orderId,
        string $stateId,
        string $shippingId,
        string $shippingMethodId,
        CalculatedPrice $calculatedPrice,
        \DateTimeInterface $shippingDateEarliest,
        \DateTimeInterface $shippingDateLatest,
        ?array $trackingCodes = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO `order_delivery` (id, version_id, order_id, order_version_id, state_id, shipping_order_address_id, shipping_order_address_version_id, shipping_method_id, tracking_codes, shipping_date_earliest, shipping_date_latest, shipping_costs, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:orderId), UNHEX(:versionId), UNHEX(:stateId), UNHEX(:shippingId), UNHEX(:versionId), UNHEX(:shippingMethodId), :trackingCodes, :dateEarliest, :dateLatest, :shippingCosts, :customFields, NOW())
            ON DUPLICATE KEY UPDATE shipping_order_address_id = UNHEX(:shippingId),
                                    shipping_method_id = UNHEX(:shippingMethodId),
                                    state_id = UNHEX(:stateId),
                                    tracking_codes = :trackingCodes,
                                    shipping_date_earliest = :dateEarliest,
                                    shipping_date_latest = :dateLatest,
                                    shipping_costs = :shippingCosts,
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderId', $orderId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('stateId', $stateId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('shippingId', $shippingId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('shippingMethodId', $shippingMethodId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('shippingCosts', json_encode($calculatedPrice, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue('trackingCodes', json_encode($trackingCodes, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue('dateEarliest', $shippingDateEarliest->format('Y-m-d'), \PDO::PARAM_STR);
        $preparedStatement->bindValue('dateLatest', $shippingDateLatest->format('Y-m-d'), \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function storeOrderDeliveryPosition(
        string $id,
        string $orderDeliveryId,
        string $orderLineItemId,
        CalculatedPrice $calculatedPrice,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO `order_delivery_position` (id, version_id, order_delivery_id, order_delivery_version_id, order_line_item_id, order_line_item_version_id, price, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:versionId), UNHEX(:orderDeliveryId), UNHEX(:versionId), UNHEX(:orderLineItemId), UNHEX(:versionId), :price, :customFields, NOW())
            ON DUPLICATE KEY UPDATE price = :price,
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderDeliveryId', $orderDeliveryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('orderLineItemId', $orderLineItemId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('price', json_encode($calculatedPrice, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }
}
