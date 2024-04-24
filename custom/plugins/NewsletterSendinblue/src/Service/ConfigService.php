<?php

namespace NewsletterSendinblue\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigService
{
    const CONFIG_PREFIX = 'Sendinblue.config.';

    const CONFIG_ACCESS_KEY = 'accessKey';
    const CONFIG_SECRET_ACCESS_KEY = 'secretAccessKey';
    const CONFIG_API_KEY = 'apiKey';

    const CONFIG_AUTH_KEY = 'authKey';
    const CONFIG_ACCESS_TOKEN = 'access_token';
    const CONFIG_REFRESH_TOKEN = 'refresh_token';

    /* Sendinblue plugin settings */
    const CONFIG_USER_CONNECTION_ID = 'userConnectionId';
    const CONFIG_IS_FULL_CUSTOMER_SYNC_ENABLED = 'isFullCustomerSyncEnabled';
    const CONFIG_CONVERSION_TRACKING = 'conversion_tracking';
    const CONFIG_IS_AUTO_SYNC_ENABLED = 'isAutoSyncEnabled';
    const CONFIG_IS_CONTACT_STATE_SYNC_ENABLED = 'isContactStateSyncEnabled';
    const CONFIG_SUBSCRIPTION_MAILING = 'subscriptionMailing';
    const CONFIG_SUBSCRIPTION_MAILING_TYPE = 'subscriptionMailingType';
    const CONFIG_IS_PAGE_TRACKING_ENABLED = 'isPageTrackingEnabled';
    const CONFIG_IS_ABANDONED_CART_TRACKING_ENABLED = 'isAbandonedCartTrackingEnabled';
    const CONFIG_MA_KEY = 'marketingAutomationKey';
    const CONFIG_MAPPED_GROUPS = 'mappedGroups';

    /* Sendinblue SMTP settings */
    const CONFIG_IS_SMTP_ENABLED = 'isSmtpEnabled';
    const CONFIG_SMTP_SENDER = 'smtpSender';
    const CONFIG_SMTP_USER = 'smtpUser';
    const CONFIG_SMTP_PASSWORD = 'smtpPassword';
    const CONFIG_SMTP_HOST = 'smtpHost';
    const CONFIG_SMTP_PORT = 'smtpPort';

    /* Shopware mailer configs */
    const CORE_MAILER_AGENT_CONFIG = 'core.mailerSettings.emailAgent';
    const CORE_MAILER_AGENT_VALUE = 'smtp';
    const CORE_MAILER_HOST_CONFIG = 'core.mailerSettings.host';
    const CORE_MAILER_PORT_CONFIG = 'core.mailerSettings.port';
    const CORE_MAILER_USERNAME_CONFIG = 'core.mailerSettings.username';
    const CORE_MAILER_PASSWORD_CONFIG = 'core.mailerSettings.password';
    const CORE_MAILER_SENDER_CONFIG = 'core.mailerSettings.senderAddress';
    const CORE_MAILER_ENCRYPTION_CONFIG = 'core.mailerSettings.encryption';
    const CORE_MAILER_ENCRYPTION_VALUE = 'tls';
    const CORE_MAILER_AUTHENTICATION_CONFIG = 'core.mailerSettings.authenticationMethod';
    const CORE_MAILER_AUTHENTICATION_VALUE = 'login';
    const CORE_MAILER_DELIVERY_DISABLED_CONFIG = 'core.mailerSettings.disableDelivery';
    
    /* Mailing type values */
    const CONFIG_SUBSCRIPTION_MAILING_TYPE_DOI = 1;
    const CONFIG_SUBSCRIPTION_MAILING_TYPE_SIMPLE = 2;

    const MICROSERVICE_FRONTEND_URL = 'https://app.brevo.com';

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var string
     */
    private $sendinblueBaseUrl;

    /**
     * @var string
     */
    private $salesChannelId;

    /**
     * Used for caching the config values
     * @var array
     */
    private $configs;

    /**
     * @param SystemConfigService $systemConfigService
     * @param string $sendinblueBaseUrl
     */
    public function __construct(SystemConfigService $systemConfigService, string $sendinblueBaseUrl)
    {
        $this->systemConfigService = $systemConfigService;
        $this->sendinblueBaseUrl = $sendinblueBaseUrl;
    }

    /**
     * @param string|null $salesChannelId
     * @param bool $checkConnection
     */
    public function setSalesChannelId(?string $salesChannelId = null, bool $checkConnection = true)
    {
        if (!empty($salesChannelId) && $checkConnection) {
            $domain = explode('.', self::CONFIG_PREFIX)[0];
            $configs = $this->systemConfigService->getDomain($domain, $salesChannelId, false);
            if (empty($configs[self::prepareConfigName(self::CONFIG_USER_CONNECTION_ID)])) {
                $salesChannelId = null;
            }
        }
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return string|null
     */
    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $config
     * @param bool $fullName
     * @return string
     */
    public static function prepareConfigName(string $config, bool $fullName = false): string
    {
        return $fullName ? $config : sprintf('%s%s', self::CONFIG_PREFIX, $config);
    }

    /**
     * @return array
     */
    public function getConfigs(): array
    {
        return $this->configs[$this->salesChannelId ?? 'global'] ?? [];
    }

    /**
     * @return string
     */
    public function getSendinblueBaseUrl(): string
    {
        return $this->sendinblueBaseUrl;
    }

    public function getSendinblueFrontEndUrl(): string
    {
        return self::MICROSERVICE_FRONTEND_URL;
    }

    /**
     * @param array $configs
     */
    public function updateSendinblueConfigs(array $configs): void
    {
        $wasSmtpEnabled = $this->isSmtpEnabled();

        foreach ($configs as $configName => $configValue) {
            if (isset($configValue)) {
                $this->setConfigValue($configName, $configValue);
            }
        }

        $isSmtpEnabled = $configs[self::CONFIG_IS_SMTP_ENABLED] ?? null;

        if (!$wasSmtpEnabled && $isSmtpEnabled) {
            $this->enableSmtp();
        }

        if ($wasSmtpEnabled && !$isSmtpEnabled) {
            $this->disableSmtp();
        }
    }

    /**
     * @return void
     */
    public function enableSmtp()
    {
        foreach ($this->getSmtpConfigMap() as $config => $value) {
            $this->setConfigValue($config, $value, true);
        }
    }

    /**
     * @return array
     */
    private function getSmtpConfigMap(): array
    {
        return [
            self::CORE_MAILER_AGENT_CONFIG => self::CORE_MAILER_AGENT_VALUE,
            self::CORE_MAILER_HOST_CONFIG => $this->getConfigValue(self::CONFIG_SMTP_HOST),
            self::CORE_MAILER_PORT_CONFIG => $this->getConfigValue(self::CONFIG_SMTP_PORT),
            self::CORE_MAILER_USERNAME_CONFIG => $this->getConfigValue(self::CONFIG_SMTP_USER),
            self::CORE_MAILER_PASSWORD_CONFIG => $this->getConfigValue(self::CONFIG_SMTP_PASSWORD),
            self::CORE_MAILER_SENDER_CONFIG => $this->getConfigValue(self::CONFIG_SMTP_SENDER),
            self::CORE_MAILER_ENCRYPTION_CONFIG => self::CORE_MAILER_ENCRYPTION_VALUE,
            self::CORE_MAILER_AUTHENTICATION_CONFIG => self::CORE_MAILER_AUTHENTICATION_VALUE
        ];
    }

    /**
     * @return void
     */
    public function disableSmtp()
    {
        foreach (array_keys($this->getSmtpConfigMap()) as $config) {
            $this->deleteConfigValue($config, true);
        }
    }

    /**
     * @param string $config
     */
    public function deleteSendinblueConfig(string $config)
    {
        $this->deleteConfigValue($config);
    }

    /**
     * @return bool|null
     */
    public function isAutoSyncEnabled(): ?bool
    {
        return $this->getConfigValue(self::CONFIG_IS_AUTO_SYNC_ENABLED);
    }

    /**
     * @return bool|null
     */
    public function isContactStateSyncEnabled(): ?bool
    {
        return $this->getConfigValue(self::CONFIG_IS_CONTACT_STATE_SYNC_ENABLED);
    }

    /**
     * @return bool|null
     */
    public function getSubscriptionMailing(): ?bool
    {
        return $this->getConfigValue(self::CONFIG_SUBSCRIPTION_MAILING);
    }

    /**
     * @return int|null
     */
    public function getSubscriptionMailingType(): ?int
    {
        return $this->getConfigValue(self::CONFIG_SUBSCRIPTION_MAILING_TYPE);
    }

    /**
     * @return bool|null
     */
    public function isPageTrackingEnabled(): ?bool
    {
        return $this->getConfigValue(self::CONFIG_IS_PAGE_TRACKING_ENABLED);
    }

    /**
     * @return bool|null
     */
    public function isAbandonedCartTrackingEnabled(): ?bool
    {
        return $this->getConfigValue(self::CONFIG_IS_ABANDONED_CART_TRACKING_ENABLED);
    }

    /**
     * @return bool|null
     */
    public function isShopMailDeliveryDisabled(): ?bool
    {
        return $this->getConfigValue(self::CORE_MAILER_DELIVERY_DISABLED_CONFIG, true);
    }

    /**
     * @return string|null
     */
    public function getShopEmailAgent(): ?string
    {
        return $this->getConfigValue(self::CORE_MAILER_AGENT_CONFIG, true);
    }

    /**
     * @return string|null
     */
    public function getUserConnectionId(): ?string
    {
        return $this->getConfigValue(self::CONFIG_USER_CONNECTION_ID);
    }

    /**
     * @return string|null
     */
    public function getMAKey(): ?string
    {
        return $this->getConfigValue(self::CONFIG_MA_KEY);
    }

    /**
     * @return string|null
     */
    public function getAccessKey(): ?string
    {
        return $this->getConfigValue(self::CONFIG_ACCESS_KEY);
    }

    /**
     * @param string $accessKey
     */
    public function setAccessKey(string $accessKey)
    {
        $this->setConfigValue(self::CONFIG_ACCESS_KEY, $accessKey);
    }

    /**
     * @return string|null
     */
    public function getSecretAccessKey(): ?string
    {
        return $this->getConfigValue(self::CONFIG_SECRET_ACCESS_KEY);
    }

    /**
     * @param string $secretAccessKey
     */
    public function setSecretAccessKey(string $secretAccessKey)
    {
        $this->setConfigValue(self::CONFIG_SECRET_ACCESS_KEY, $secretAccessKey);
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->getConfigValue(self::CONFIG_API_KEY);
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey)
    {
        $this->setConfigValue(self::CONFIG_API_KEY, $apiKey);
    }

    /**
     * @return string|null
     */
    public function getAuthKey(): ?string
    {
        return $this->getConfigValue(self::CONFIG_AUTH_KEY);
    }

    /**
     * @param string $authKey
     */
    public function setAuthKey(string $authKey)
    {
        $this->setConfigValue(self::CONFIG_AUTH_KEY, $authKey);
    }

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->getConfigValue(self::CONFIG_ACCESS_TOKEN);
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken)
    {
        $this->setConfigValue(self::CONFIG_ACCESS_TOKEN, $accessToken);
    }

    /**
     * @param string $userConnectionId
     */
    public function setUserConnectionId(string $userConnectionId)
    {
        $this->setConfigValue(self::CONFIG_USER_CONNECTION_ID, $userConnectionId);
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->getConfigValue(self::CONFIG_REFRESH_TOKEN);
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken(string $refreshToken)
    {
        $this->setConfigValue(self::CONFIG_REFRESH_TOKEN, $refreshToken);
    }

    public function isFullCustomerSyncEnabled(): ?bool
    {
        return $this->getConfigValue(self::CONFIG_IS_FULL_CUSTOMER_SYNC_ENABLED);
    }

    /**
     * @return string|null
     */
    public function getConversionTracking(): ?string
    {
        return $this->getConfigValue(self::CONFIG_CONVERSION_TRACKING);
    }

    /**
     * @param string $conversionTracking
     */
    public function setConversionTracking(string $conversionTracking)
    {
        $this->setConfigValue(self::CONFIG_CONVERSION_TRACKING, $conversionTracking);
    }

    /**
     * @return array|null
     */
    public function getMappedGroups(): ?array
    {
        return $this->getConfigValue(self::CONFIG_MAPPED_GROUPS);
    }

    /**
     * @return bool|null
     */
    public function isSmtpEnabled(): ?bool
    {
        return $this->getConfigValue(self::CONFIG_IS_SMTP_ENABLED);
    }

    /**
     * @param bool $value
     */
    public function setIsSmtpEnabled(bool $value)
    {
        $this->setConfigValue(self::CONFIG_IS_SMTP_ENABLED, $value);
    }

    /**
     * @return string|null
     */
    public function getSmtpHost(): ?string
    {
        return $this->getConfigValue(self::CONFIG_SMTP_HOST);
    }

    /**
     * @param string $config
     * @param bool $fullName
     * @return mixed|null
     */
    private function getConfigValue(string $config, bool $fullName = false)
    {
        $configName = self::prepareConfigName($config, $fullName);
        $cacheKey = $this->salesChannelId ?? 'global';
        if (empty($this->configs[$cacheKey]) || !isset($this->configs[$cacheKey][$configName])) {
            if (strpos($configName, self::CONFIG_PREFIX) === 0) {
                $domain = explode('.', self::CONFIG_PREFIX)[0];
                $this->configs[$cacheKey] = $this->systemConfigService->getDomain($domain, $this->salesChannelId, false);
            } else {
                $this->configs[$cacheKey][$configName] = $this->systemConfigService->get($configName, $this->salesChannelId);
            }
        }
        return !empty($this->configs[$cacheKey]) && isset($this->configs[$cacheKey][$configName]) ? $this->configs[$cacheKey][$configName] : null;
    }

    /**
     * @param string $config
     * @param $value
     * @param bool $fullName
     */
    private function setConfigValue(string $config, $value, bool $fullName = false)
    {
        $configName = self::prepareConfigName($config, $fullName);
        $this->systemConfigService->set($configName, $value, $this->salesChannelId);
        // shopware does not support having different mailer configs per sales channel
        if (!empty($this->salesChannelId) && in_array($configName, array_keys($this->getSmtpConfigMap()))) {
            $this->systemConfigService->set($configName, $value);
        }
    }

    /**
     * @param string $config
     * @param bool $fullName
     */
    private function deleteConfigValue(string $config, bool $fullName = false)
    {
        $configName = self::prepareConfigName($config, $fullName);
        $this->systemConfigService->delete($configName, $this->salesChannelId);
        // shopware does not support having different mailer configs per sales channel
        if (!empty($this->salesChannelId) && in_array($configName, array_keys($this->getSmtpConfigMap()))) {
            $this->systemConfigService->delete($configName);
        }
    }
}
