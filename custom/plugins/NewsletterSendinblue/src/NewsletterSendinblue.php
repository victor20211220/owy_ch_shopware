<?php declare(strict_types=1);

namespace NewsletterSendinblue;

use Doctrine\DBAL\Connection;
use NewsletterSendinblue\Service\ConfigService;
use NewsletterSendinblue\Service\IntegrationService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NewsletterSendinblue extends Plugin
{
    public const ACL_ROLE_NAME = 'Brevo';
    public const OLD_ACL_ROLE_NAME = 'Sendinblue';
    public const INTEGRATION_LABEL = 'Brevo';
    public const OLD_INTEGRATION_LABEL = 'Sendinblue';
    public const PLUGIN_LABEL = 'Sendinblue';

    /**
     * @var IntegrationService
     */
    private $integrationService;

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->setParameter('sendinblue.service_worker_path', $this->getPath() . '/Resources/js/service-worker.js');
    }

    /**
     * @param InstallContext $installContext
     */
    public function postInstall(InstallContext $installContext): void
    {
        $this->createAclRole($installContext->getContext());
        $this->createIntegrations($installContext->getContext());
    }

    /**
     * @param UpdateContext $updateContext
     * @return void
     */
    public function update(UpdateContext $updateContext): void
    {
        if (version_compare($updateContext->getCurrentPluginVersion(), '4.0.3', '<=')) {
            try {
                $this->changeIntegrationAndRoleName();
            } catch (\Throwable $exception) {
            }
        }
    }

    /**
     * @param DeactivateContext $deactivateContext
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->removeSIBSmtpSettings();
    }

    /**
     * @param UninstallContext $uninstallContext
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$uninstallContext->keepUserData()) {
            $this->deleteIntegrations($uninstallContext->getContext());
            $this->deleteAclRole($uninstallContext->getContext());
            $this->deleteAllSendinblueConfigs();
        }
    }

    /**
     * @param Context $context
     * @return string
     */
    private function createAclRole(Context $context)
    {
        $id = md5(self::ACL_ROLE_NAME);
        $roleData = [
            'id' => $id,
            'name' => self::ACL_ROLE_NAME,
            'privileges' => ["customer.editor", "customer.viewer", "customer:read", "customer:update", "newsletter_recipient.creator", "newsletter_recipient.deleter", "newsletter_recipient.editor", "newsletter_recipient.viewer", "newsletter_recipient:create", "newsletter_recipient:delete", "newsletter_recipient:read", "newsletter_recipient:update"]
        ];
        $aclRoleRepository = $this->container->get('acl_role.repository');
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($aclRoleRepository, $roleData): void {
            $aclRoleRepository->upsert([$roleData], $context);
        });
        return $id;
    }

    /**
     * @param Context $context
     */
    private function createIntegrations(Context $context): void
    {
        $this->getIntegrationService()->createIntegration(self::INTEGRATION_LABEL, $context);
    }

    /**
     * @param Context $context
     */
    private function deleteIntegrations(Context $context): void
    {
        $this->getIntegrationService()->deleteIntegrations($context);
    }

    /**
     * @param Context $context
     */
    private function deleteAclRole(Context $context)
    {
        $aclRoleRepository = $this->container->get('acl_role.repository');
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($aclRoleRepository): void {
            $aclRoleRepository->delete([['id' => md5(self::ACL_ROLE_NAME)]], $context);
            $aclRoleRepository->delete([['id' => md5(self::OLD_ACL_ROLE_NAME)]], $context);
        });
    }

    private function deleteAllSendinblueConfigs(): void
    {
        /** @var EntityRepository $systemConfigRepository */
        $systemConfigRepository = $this->container->get('system_config.repository');

        $this->removeSIBSmtpSettings();

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('configurationKey', ConfigService::CONFIG_PREFIX));
        $systemConfigIds = $systemConfigRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
        if (empty($systemConfigIds)) {
            return;
        }

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $systemConfigIds);

        $systemConfigRepository->delete($ids, Context::createDefaultContext());
    }

    /**
     * @return void
     */
    private function removeSIBSmtpSettings(): void
    {
        /** @var EntityRepository $systemConfigRepository */
        $systemConfigRepository = $this->container->get('system_config.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('configurationKey', ConfigService::prepareConfigName(ConfigService::CONFIG_IS_SMTP_ENABLED)));
        $systemConfigs = $systemConfigRepository->search($criteria, Context::createDefaultContext())->getElements();
        if (empty($systemConfigs)) {
            return;
        }
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);

        $smtpConfigs = [
            ConfigService::CORE_MAILER_AGENT_CONFIG,
            ConfigService::CORE_MAILER_HOST_CONFIG,
            ConfigService::CORE_MAILER_PORT_CONFIG,
            ConfigService::CORE_MAILER_USERNAME_CONFIG,
            ConfigService::CORE_MAILER_PASSWORD_CONFIG,
            ConfigService::CORE_MAILER_SENDER_CONFIG,
            ConfigService::CORE_MAILER_ENCRYPTION_CONFIG,
            ConfigService::CORE_MAILER_AUTHENTICATION_CONFIG
        ];

        foreach ($systemConfigs as $systemConfig) {
            if ($systemConfig->getConfigurationValue()) {
                $salesChannelId = $systemConfig->getSalesChannelId();

                foreach ($smtpConfigs as $config) {
                    $systemConfigService->delete($config, $salesChannelId);
                }
            }
        }
        foreach ($smtpConfigs as $config) {
            $systemConfigService->delete($config);
        }
    }

    /**
     * @return IntegrationService|null
     */
    private function getIntegrationService(): ?IntegrationService
    {
        if (empty($this->integrationService)) {
            if ($this->container->has(IntegrationService::class)) {
                /** @var IntegrationService integrationService */
                $this->integrationService = $this->container->get(IntegrationService::class);
            } else {
                /** @var EntityRepository $integrationRepository */
                $integrationRepository = $this->container->get('integration.repository');
                /** @var EntityRepository $aclRoleRepository */
                $aclRoleRepository = $this->container->get('acl_role.repository');
                /** @var SystemConfigService $systemConfigService */
                $systemConfigService = $this->container->get(SystemConfigService::class);
                $this->integrationService = new IntegrationService($integrationRepository, $aclRoleRepository, $systemConfigService);
            }
        }
        return $this->integrationService;
    }

    /**
     * @return void
     */
    private function changeIntegrationAndRoleName(): void
    {
        $connection = $this->container->get(Connection::class);

        // change acl_role name
        try {
            $connection->update('acl_role',
                ['name' => self::ACL_ROLE_NAME],
                ['name' => self::OLD_ACL_ROLE_NAME]
            );
        } catch (\Throwable $e) {
        }

        // change integration name
        try {
            $integrationRepository = $this->container->get('integration.repository');
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('customFields.sendinblue', 1));
            $integrations = $integrationRepository->search($criteria, Context::createDefaultContext());
            $data = [];
            foreach ($integrations as $integration) {
                $data[] = [
                    'id' => $integration->getId(),
                    'label' => str_replace(self::OLD_INTEGRATION_LABEL, self::INTEGRATION_LABEL, $integration->getLabel())
                ];
            }

            if (!empty($data)) {
                $integrationRepository->update($data, Context::createDefaultContext());
            }
        } catch (\Throwable $e) {
        }
    }
}
