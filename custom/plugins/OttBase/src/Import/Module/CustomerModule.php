<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

final class CustomerModule extends BaseModule
{
    private array $customerGroupCache = [];
    private array $paymentMethodCache = [];
    private array $salutationCache = [];
    private array $countryCache = [];
    private ?string $defaultPaymentMethodId = null;
    private ?string $defaultCustomerGroupId = null;
    private ?string $defaultSalutationId = null;
    private ?string $defaultCountryId = null;

    public function selectCustomer(string $customerId): ?array
    {
        $statement = <<<'SQL'
            SELECT *, HEX(salutation_id) as salutation_id FROM customer WHERE id = UNHEX(:customerId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('customerId', $customerId, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetch();
        if (false === $result) {
            return null;
        }

        return $result;
    }

    public function selectCustomerId(string $customerNumber): ?string
    {
        $statement = <<<'SQL'
            SELECT HEX(id) FROM customer WHERE customer_number = :customerNumber
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('customerNumber', $customerNumber, \PDO::PARAM_STR);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function selectCustomerAddressId(string $customerId, ?string $billingAddressId = null): ?string
    {
        $statement = <<<'SQL'
            SELECT HEX(id) FROM customer_address WHERE customer_id = UNHEX(:customerId) %s
            SQL;

        $preparedStatement = $this->connection->prepare(
            sprintf(
                $statement,
                null === $billingAddressId
                    ? ''
                    : 'AND id != UNHEX(:billingAddressId)'
            )
        );
        $preparedStatement->bindValue('customerId', $customerId, \PDO::PARAM_STR);
        if (null !== $billingAddressId) {
            $preparedStatement->bindValue('billingAddressId', $billingAddressId, \PDO::PARAM_STR);
        }

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return (string) $result;
    }

    public function storeCustomer(
        string $id,
        string $customerGroupId,
        string $defaultPaymentMethodId,
        string $salesChannelId,
        string $languageId,
        string $billingAddressId,
        string $shippingAddressId,
        string $customerNumber,
        string $salutationId,
        string $email,
        string $firstName,
        string $lastName,
        ?bool $active = true,
        ?bool $guest = false,
        ?int $orderCount = 0,
        ?string $company = null,
        ?string $title = null,
        ?string $birthday = null,
        ?array $customFields = null,
        ?string $lastOrderDate = null,
        ?string $remoteAddress = null,
        ?string $firstLogin = null,
        ?string $lastLogin = null,
        ?string $password = null,
        ?string $legacyPassword = null,
        ?string $legacyEncoder = null,
        ?string $lastPaymentMethodId = null,
        ?bool $doubleOptInRegistration = true,
        ?string $doubleOptInEmailSentDate = null,
        ?string $doubleOptInConfirmDate = null,
        ?string $hash = null,
        ?string $affiliateCode = null,
        ?string $campaignCode = null,
        ?string $boundSalesChannelId = null,
        ?array $vatIds = null,
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO customer (id, customer_group_id, default_payment_method_id, sales_channel_id, language_id, last_payment_method_id, default_billing_address_id, default_shipping_address_id, customer_number, salutation_id, first_name, last_name, company, password, legacy_password, legacy_encoder, email, title, active, double_opt_in_registration, double_opt_in_email_sent_date, double_opt_in_confirm_date, hash, guest, first_login, last_login, birthday, last_order_date, order_count, custom_fields, affiliate_code, campaign_code, created_at, remote_address, bound_sales_channel_id, vat_ids)
            VALUES (UNHEX(:id), UNHEX(:customerGroupId), UNHEX(:defaultPaymentMethodId), UNHEX(:salesChannelId), UNHEX(:languageId), UNHEX(:lastPaymentMethodId), UNHEX(:defaultBillingAddressId), UNHEX(:defaultShippingAddressId), :customerNumber, UNHEX(:salutationId), :firstName, :lastName, :company, :password, :legacyPassword, :legacyEncoder, :email, :title, :active, :doubleOptInRegistration, :doubleOptInEmailSentDate, :doubleOptInConfirmDate, :hash, :guest, :firstLogin, :lastLogin,:birthday, :lastOrderDate, :orderCount, :customFields, :affiliateCode, :campaignCode, NOW(), :remoteAddress, UNHEX(:boundSalesChannelId), :vatIds)
            ON DUPLICATE KEY UPDATE customer_group_id = UNHEX(:customerGroupId), default_payment_method_id = UNHEX(:defaultPaymentMethodId), sales_channel_id = UNHEX(:salesChannelId), language_id = UNHEX(:languageId), last_payment_method_id = UNHEX(:lastPaymentMethodId), default_billing_address_id = UNHEX(:defaultBillingAddressId), default_shipping_address_id = UNHEX(:defaultShippingAddressId), customer_number = :customerNumber, salutation_id = UNHEX(:salutationId), first_name = :firstName, last_name = :lastName, company = :company, password = :password, legacy_password = :legacyPassword, legacy_encoder = :legacyEncoder, email = :email, title =  :title, active = :active, double_opt_in_registration = :doubleOptInRegistration, double_opt_in_email_sent_date = :doubleOptInEmailSentDate, double_opt_in_confirm_date = :doubleOptInConfirmDate, hash = :hash, guest = :guest, first_login = :firstLogin, last_login = :lastLogin, birthday = :birthday, last_order_date = :lastOrderDate, order_count = :orderCount, custom_fields = :customFields, affiliate_code = :affiliateCode, campaign_code = :campaignCode, updated_at = NOW(), remote_address = :remoteAddress, bound_sales_channel_id = UNHEX(:boundSalesChannelId), vat_ids = :vatIds
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('customerGroupId', $customerGroupId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('defaultPaymentMethodId', $defaultPaymentMethodId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('lastPaymentMethodId', $lastPaymentMethodId);
        $preparedStatement->bindValue('defaultBillingAddressId', $billingAddressId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('defaultShippingAddressId', $shippingAddressId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salutationId', $salutationId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('customerNumber', $customerNumber, \PDO::PARAM_STR);
        $preparedStatement->bindValue('email', $email, \PDO::PARAM_STR);
        $preparedStatement->bindValue('firstName', $firstName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('lastName', $lastName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('company', $company);
        $preparedStatement->bindValue('title', $title);
        $preparedStatement->bindValue('birthday', $birthday);
        $preparedStatement->bindValue('active', $active, \PDO::PARAM_BOOL);
        $preparedStatement->bindValue('guest', $guest, \PDO::PARAM_BOOL);
        $preparedStatement->bindValue('doubleOptInRegistration', $doubleOptInRegistration, \PDO::PARAM_BOOL);
        $preparedStatement->bindValue('orderCount', $orderCount, \PDO::PARAM_INT);
        $preparedStatement->bindValue('customFields', empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('vatIds', empty($vatIds) ? null : json_encode($vatIds, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('lastOrderDate', $lastOrderDate);
        $preparedStatement->bindValue('remoteAddress', $remoteAddress);
        $preparedStatement->bindValue('firstLogin', $firstLogin);
        $preparedStatement->bindValue('lastLogin', $lastLogin);
        $preparedStatement->bindValue('password', $password);
        $preparedStatement->bindValue('legacyPassword', $legacyPassword);
        $preparedStatement->bindValue('legacyEncoder', $legacyEncoder);
        $preparedStatement->bindValue('doubleOptInEmailSentDate', $doubleOptInEmailSentDate);
        $preparedStatement->bindValue('doubleOptInConfirmDate', $doubleOptInConfirmDate);
        $preparedStatement->bindValue('hash', $hash);
        $preparedStatement->bindValue('affiliateCode', $affiliateCode);
        $preparedStatement->bindValue('campaignCode', $campaignCode);
        $preparedStatement->bindValue('boundSalesChannelId', $boundSalesChannelId);
        $preparedStatement->executeStatement();
    }

    public function storeCustomerAddress(
        string $id,
        string $customerId,
        string $countryId,
        string $salutationId,
        string $firstName,
        string $lastName,
        string $street,
        ?string $zipCode,
        string $city,
        ?string $phoneNumber = null,
        ?string $company = null,
        ?string $department = null,
        ?string $countryStateId = null,
        ?string $title = null,
        ?string $additionalOne = null,
        ?string $additionalTwo = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO customer_address (id, customer_id, country_id, country_state_id, company, department, salutation_id, title, first_name, last_name, street, zipcode, city, phone_number, additional_address_line1, additional_address_line2, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:customerId), UNHEX(:countryId), UNHEX(:countryStateId), :company, :department, UNHEX(:salutationId), :title, :firstName, :lastName, :street, :zipCode, :city, :phoneNumber, :additionalAddressLine1, :additionalAddressLine2, :customFields, NOW())
            ON DUPLICATE KEY UPDATE customer_id = UNHEX(:customerId), country_id = UNHEX(:countryId), country_state_id = UNHEX(:countryStateId), company = :company, department = :department, salutation_id = UNHEX(:salutationId), title = :title, first_name = :firstName, last_name = :lastName, street = :street, zipcode = :zipCode, city = :city, phone_number = :phoneNumber, additional_address_line1 = :additionalAddressLine1, additional_address_line2 = :additionalAddressLine2, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salutationId', $salutationId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('customerId', $customerId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('countryId', $countryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('countryStateId', $countryStateId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('firstName', $firstName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('lastName', $lastName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('street', $street, \PDO::PARAM_STR);
        $preparedStatement->bindValue('zipCode', $zipCode);
        $preparedStatement->bindValue('city', $city, \PDO::PARAM_STR);
        $preparedStatement->bindValue('phoneNumber', $phoneNumber);
        $preparedStatement->bindValue('company', $company);
        $preparedStatement->bindValue('department', $department);
        $preparedStatement->bindValue('title', $title);
        $preparedStatement->bindValue('additionalAddressLine1', $additionalOne);
        $preparedStatement->bindValue('additionalAddressLine2', $additionalTwo);
        $preparedStatement->bindValue('customFields', empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));
        $preparedStatement->executeStatement();
    }

    public function getCustomerGroupId(string $customerGroup, bool $withDefault = true): ?string
    {
        if (!isset($this->customerGroupCache[$customerGroup])) {
            $statement = <<<'SQL'
                SELECT HEX(cg.id) FROM customer_group cg JOIN customer_group_translation cgt ON cg.id = cgt.customer_group_id WHERE cgt.name = :name COLLATE utf8mb4_bin
                SQL;
            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $customerGroup, \PDO::PARAM_STR);

            $customerGroupId = $preparedStatement->executeQuery()->fetchOne();

            if (false === $customerGroupId || null === $customerGroupId) {
                if (false === $withDefault) {
                    return null;
                }
                $this->customerGroupCache[$customerGroup] = $this->getDefaultCustomerGroupId();
            } else {
                $this->customerGroupCache[$customerGroup] = $customerGroupId;
            }
        }

        return $this->customerGroupCache[$customerGroup];
    }

    public function storeCustomerGroup(string $id, bool $displayGross, bool $registrationActive): void
    {
        $statement = <<<'SQL'
            INSERT INTO customer_group (id, display_gross, registration_active, created_at)
            VALUES (UNHEX(:id), :displayGross, :registrationActive, NOW())
            ON DUPLICATE KEY UPDATE display_gross = :displayGross, registration_active = :registrationActive, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('displayGross', $displayGross, \PDO::PARAM_INT);
        $preparedStatement->bindValue('registrationActive', $registrationActive, \PDO::PARAM_INT);
        $preparedStatement->executeStatement();
    }

    public function storeCustomerGroupTranslation(
        string $id,
        string $languageId,
        ?string $name = null,
        ?string $registrationTitle = null,
        ?string $registrationIntroduction = null,
        ?bool $registrationOnlyCompanyRegistration = null,
        ?string $registrationSeoMetaDescription = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO customer_group_translation (customer_group_id, language_id, name, custom_fields, registration_title, registration_introduction, registration_only_company_registration, registration_seo_meta_description, created_at)
            VALUES (UNHEX(:id), UNHEX(:languageId), :name, :customFields, :registrationTitle, :registrationIntroduction, :registrationOnlyCompanyRegistration, :registrationSeoMetaDescription, NOW())
            ON DUPLICATE KEY UPDATE
                                    name = :name,
                                    custom_fields = :customFields,
                                    updated_at = NOW(),
                                    registration_title = :registrationTitle,
                                    registration_introduction = :registrationIntroduction,
                                    registration_only_company_registration = :registrationOnlyCompanyRegistration,
                                    registration_seo_meta_description = :registrationSeoMetaDescription
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('registrationTitle', $registrationTitle, \PDO::PARAM_STR);
        $preparedStatement->bindValue('registrationIntroduction', $registrationIntroduction, \PDO::PARAM_STR);
        $preparedStatement->bindValue('registrationOnlyCompanyRegistration', $registrationOnlyCompanyRegistration, \PDO::PARAM_INT);
        $preparedStatement->bindValue('registrationSeoMetaDescription', $registrationSeoMetaDescription, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function getDefaultCustomerGroupId(): string
    {
        if (null === $this->defaultCustomerGroupId) {
            $statement = <<<'SQL'
                SELECT HEX(cg.id) FROM customer_group cg JOIN customer_group_translation cgt on cg.id = cgt.customer_group_id WHERE cgt.name = 'Standard-Kundengruppe'
                SQL;

            $preparedStatement = $this->connection->prepare($statement);

            $this->defaultCustomerGroupId = $preparedStatement->executeQuery()->fetchOne();
        }

        if (empty($this->defaultCustomerGroupId)) {
            throw new \Exception('Could not find default customer group');
        }

        return $this->defaultCustomerGroupId;
    }

    public function getPaymentMethodId(string $paymentMethod): string
    {
        if (!isset($this->paymentMethodCache[$paymentMethod])) {
            $statement = <<<'SQL'
                SELECT HEX(p.id) FROM payment_method p JOIN payment_method_translation pt ON p.id = pt.payment_method_id WHERE pt.name = :name COLLATE utf8mb4_bin
                SQL;
            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $paymentMethod, \PDO::PARAM_STR);

            $paymentMethodId = $preparedStatement->executeQuery()->fetchOne();

            if (false === $paymentMethodId || null === $paymentMethodId) {
                $this->paymentMethodCache[$paymentMethod] = $this->getDefaultPaymentMethodId();
            } else {
                $this->paymentMethodCache[$paymentMethod] = $paymentMethodId;
            }
        }

        return $this->paymentMethodCache[$paymentMethod];
    }

    public function getDefaultPaymentMethodId(): string
    {
        if (null === $this->defaultPaymentMethodId) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM payment_method WHERE handler_identifier = 'Shopware\\Core\\Checkout\\Payment\\Cart\\PaymentHandler\\PrePayment'
                SQL;

            $preparedStatement = $this->connection->prepare($statement);

            $this->defaultPaymentMethodId = $preparedStatement->executeQuery()->fetchOne();
        }

        if (empty($this->defaultPaymentMethodId)) {
            throw new \Exception('Could not find default payment method');
        }

        return $this->defaultPaymentMethodId;
    }

    public function getSalutationId(string $salutation): string
    {
        if (!isset($this->salutationCache[$salutation])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM salutation WHERE salutation_key = :key
                SQL;
            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('key', $salutation, \PDO::PARAM_STR);

            $salutationId = $preparedStatement->executeQuery()->fetchOne();

            if (false === $salutationId || null === $salutationId) {
                $this->salutationCache[$salutation] = $this->getDefaultSalutationId();
            } else {
                $this->salutationCache[$salutation] = $salutationId;
            }
        }

        return $this->salutationCache[$salutation];
    }

    public function getDefaultSalutationId(): string
    {
        if (null === $this->defaultSalutationId) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM salutation WHERE salutation_key = 'not_specified'
                SQL;

            $preparedStatement = $this->connection->prepare($statement);

            $this->defaultSalutationId = $preparedStatement->executeQuery()->fetchOne();
        }

        if (empty($this->defaultSalutationId)) {
            throw new \Exception('Could not find default salutation');
        }

        return $this->defaultSalutationId;
    }

    public function getCountryId(string $country): string
    {
        if (!isset($this->countryCache[$country])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM country WHERE iso = :key
                SQL;
            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('key', $country, \PDO::PARAM_STR);

            $countryId = $preparedStatement->executeQuery()->fetchOne();

            $this->countryCache[$country] = false === $countryId || null === $countryId ? $this->getDefaultCountryId() : $countryId;
        }

        return $this->countryCache[$country];
    }

    public function getDefaultCountryId(): string
    {
        if (null === $this->defaultCountryId) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM country WHERE iso = 'DE'
                SQL;

            $preparedStatement = $this->connection->prepare($statement);

            $this->defaultCountryId = $preparedStatement->executeQuery()->fetchOne();
        }

        if (empty($this->defaultCountryId)) {
            throw new \Exception('Could not find default country');
        }

        return $this->defaultCountryId;
    }
}
