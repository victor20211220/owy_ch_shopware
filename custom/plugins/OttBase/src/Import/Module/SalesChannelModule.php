<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

use Shopware\Core\Defaults;

final class SalesChannelModule extends BaseModule
{
    private array $snippetSetIdCache = [];
    private array $salesChannelCache = [];
    private array $salesChannelIdCache = [];
    private array $salesChannelTypeIdCache = [];
    private array $domainIdCache = [];

    public function selectSalesChannels(): array
    {
        $result = [];
        if (!$this->isCacheEnabled() || empty($this->salesChannelCache)) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(id)) as id,
                       LOWER(HEX(navigation_category_id)) as navigation_category_id,
                       sctt.name as type
                FROM sales_channel
                JOIN sales_channel_type_translation sctt ON sales_channel.type_id = sctt.sales_channel_type_id AND sctt.language_id = UNHEX(:languageId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('languageId', Defaults::LANGUAGE_SYSTEM, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchAllAssociative();
            if ($this->isCacheEnabled()) {
                $this->salesChannelCache = $result;
            }
        }

        return $this->isCacheEnabled() ? $this->salesChannelCache : $result;
    }

    public function selectSalesChannelId(string $name): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->salesChannelIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT HEX(sales_channel_id) FROM sales_channel_translation WHERE name = :name COLLATE utf8mb4_bin
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->salesChannelIdCache[$name] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->salesChannelIdCache[$name] : (string) $result;
    }

    public function selectSalesChannelTypeId(string $name): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->salesChannelTypeIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT HEX(sales_channel_type_id) FROM sales_channel_type_translation WHERE name = :name COLLATE utf8mb4_bin
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->salesChannelTypeIdCache[$name] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->salesChannelTypeIdCache[$name] : (string) $result;
    }

    public function selectDomainId(string $url): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->domainIdCache[$url])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM sales_channel_domain WHERE url = :url
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('url', $url, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->domainIdCache[$url] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->domainIdCache[$url] : (string) $result;
    }

    public function selectSnippetSetId(string $localeIso): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->snippetSetIdCache[$localeIso])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM snippet_set WHERE iso = :localeIso
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('localeIso', $localeIso, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->snippetSetIdCache[$localeIso] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->snippetSetIdCache[$localeIso] : (string) $result;
    }

    public function storeSalesChannel(
        string $id,
        string $typeId,
        string $languageId,
        string $currencyId,
        string $customerGroupId,
        string $countryId,
        string $categoryId,
        ?string $footerCategoryId = null,
        ?string $serviceCategoryId = null,
        ?string $paymentMethodId = null,
        ?string $shippingMethodId = null,
        ?string $mailHeaderId = null,
        ?string $accessKey = null,
        ?string $shortName = null,
        ?array $configuration = null,
        ?string $maintenanceIpWhitelist = null,
        ?array $paymentMethodIds = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO sales_channel (id, type_id, short_name, configuration, access_key, language_id, currency_id, payment_method_id, shipping_method_id, country_id, navigation_category_id, navigation_category_version_id, footer_category_id, footer_category_version_id, service_category_id, service_category_version_id, maintenance_ip_whitelist, customer_group_id, mail_header_footer_id, payment_method_ids, created_at)
            VALUES (UNHEX(:id), UNHEX(:typeId), :shortName, :configuration, :accessKey, UNHEX(:languageId), UNHEX(:currencyId), UNHEX(:paymentMethodId), UNHEX(:shippingMethodId), UNHEX(:countryId), UNHEX(:navigationCategoryId), UNHEX(:versionId), UNHEX(:footerCategoryId), UNHEX(:versionId), UNHEX(:serviceCategoryId), UNHEX(:versionId), :maintenanceIpWhitelist, UNHEX(:customerGroupId), UNHEX(:mailHeaderId), :paymentMethodIds, NOW())
            ON DUPLICATE KEY UPDATE language_id = UNHEX(:languageId), currency_id = UNHEX(:currencyId), updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('versionId', $this->getVersionId(), \PDO::PARAM_STR);
        $preparedStatement->bindValue('typeId', $typeId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('currencyId', $currencyId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('countryId', $countryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('customerGroupId', $customerGroupId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('navigationCategoryId', $categoryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('footerCategoryId', $footerCategoryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('serviceCategoryId', $serviceCategoryId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('paymentMethodId', $paymentMethodId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('shippingMethodId', $shippingMethodId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('mailHeaderId', $mailHeaderId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('accessKey', $accessKey, \PDO::PARAM_STR);
        $preparedStatement->bindValue('shortName', $shortName, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'configuration',
            null === $configuration
            ? null :
            json_encode($configuration, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->bindValue(
            'paymentMethodIds',
            null === $paymentMethodIds
            ? null
            : json_encode($paymentMethodIds, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->bindValue('maintenanceIpWhitelist', $maintenanceIpWhitelist, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeSalesChannelTranslation(
        string $salesChannelId,
        string $languageId,
        ?string $name = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO sales_channel_translation (sales_channel_id, language_id, name, custom_fields, created_at)
            VALUES (UNHEX(:salesChannelId), UNHEX(:languageId), :name, :customFields, NOW())
            ON DUPLICATE KEY UPDATE name = :name, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function storeSalesChannelDomain(
        string $id,
        string $salesChannelId,
        string $languageId,
        string $url,
        string $currencyId,
        string $snippetSetId,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO sales_channel_domain (id, sales_channel_id, language_id, url, currency_id, snippet_set_id, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:salesChannelId), UNHEX(:languageId), :url, UNHEX(:currencyId), UNHEX(:snippetSetId),:customFields, NOW())
            ON DUPLICATE KEY UPDATE language_id = UNHEX(:languageId),
                                    url = :url,
                                    currency_id = UNHEX(:currencyId),
                                    snippet_set_id = UNHEX(:snippetSetId),
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('url', $url, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('currencyId', $currencyId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('snippetSetId', $snippetSetId, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function resetSalesChannelCountries(string $salesChannelId): void
    {
        $statement = <<<'SQL'
            DELETE FROM sales_channel_country WHERE sales_channel_id = UNHEX(:salesChannelId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeSalesChannelCountry(string $salesChannelId, string $countryId): void
    {
        $statement = <<<'SQL'
            INSERT INTO sales_channel_country (sales_channel_id, country_id)
            VALUES (UNHEX(:salesChannelId), UNHEX(:countryId))
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('countryId', $countryId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetSalesChannelCurrencies(string $salesChannelId): void
    {
        $statement = <<<'SQL'
            DELETE FROM sales_channel_currency WHERE sales_channel_id = UNHEX(:salesChannelId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeSalesChannelCurrency(string $salesChannelId, string $currencyId): void
    {
        $statement = <<<'SQL'
            INSERT INTO sales_channel_currency (sales_channel_id, currency_id)
            VALUES (UNHEX(:salesChannelId), UNHEX(:currencyId))
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('currencyId', $currencyId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetSalesChannelLanguages(string $salesChannelId): void
    {
        $statement = <<<'SQL'
            DELETE FROM sales_channel_language WHERE sales_channel_id = UNHEX(:salesChannelId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeSalesChannelLanguage(string $salesChannelId, string $languageId): void
    {
        $statement = <<<'SQL'
            INSERT INTO sales_channel_language (sales_channel_id, language_id)
            VALUES (UNHEX(:salesChannelId), UNHEX(:languageId))
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetSalesChannelPaymentMethods(string $salesChannelId): void
    {
        $statement = <<<'SQL'
            DELETE FROM sales_channel_payment_method WHERE sales_channel_id = UNHEX(:salesChannelId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeSalesChannelPaymentMethod(string $salesChannelId, string $paymentMethodId): void
    {
        $statement = <<<'SQL'
            INSERT INTO sales_channel_payment_method (sales_channel_id, payment_method_id)
            VALUES (UNHEX(:salesChannelId), UNHEX(:paymentMethodId))
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('paymentMethodId', $paymentMethodId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetSalesChannelShippingMethods(string $salesChannelId): void
    {
        $statement = <<<'SQL'
            DELETE FROM sales_channel_shipping_method WHERE sales_channel_id = UNHEX(:salesChannelId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeSalesChannelShippingMethod(string $salesChannelId, string $shippingMethod): void
    {
        $statement = <<<'SQL'
            INSERT INTO sales_channel_shipping_method (sales_channel_id, shipping_method_id)
            VALUES (UNHEX(:salesChannelId), UNHEX(:shippingMethod))
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('shippingMethod', $shippingMethod, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }
}
