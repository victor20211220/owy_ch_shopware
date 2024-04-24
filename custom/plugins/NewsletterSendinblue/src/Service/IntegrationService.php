<?php

namespace NewsletterSendinblue\Service;

use NewsletterSendinblue\NewsletterSendinblue;
use NewsletterSendinblue\Service\ConfigService;
use Shopware\Core\Framework\Api\Acl\Role\AclRoleEntity;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Integration\IntegrationEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class IntegrationService
{

    /** @var EntityRepository */
    private $integrationRepository;

    /** @var EntityRepository */
    private $aclRoleRepository;

    /** @var SystemConfigService */
    private $systemConfigService;

    /**
     * @param EntityRepository $integrationRepository
     * @param EntityRepository $aclRoleRepository
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(EntityRepository $integrationRepository, EntityRepository $aclRoleRepository, SystemConfigService $systemConfigService)
    {
        $this->integrationRepository = $integrationRepository;
        $this->aclRoleRepository = $aclRoleRepository;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param string $label
     * @param Context $context
     * @return IntegrationEntity|null
     */
    public function getIntegration(string $label, Context $context): ?IntegrationEntity
    {
        return $this->integrationRepository->search(new Criteria([md5($label)]), $context)->first();
    }

    /**
     * @param string $label
     * @param Context $context
     * @param string|null $salesChannelId
     */
    public function createIntegration(string $label, Context $context, ?string $salesChannelId = null): void
    {
        $accessKey = AccessKeyHelper::generateAccessKey('integration');
        $secretAccessKey = AccessKeyHelper::generateSecretAccessKey();

        $roles = [];
        if ($role = $this->getAclRole($context)) {
            $roles[] = ['id' => $role->getId()];
        }
        $this->integrationRepository->upsert([[
            'id' => md5($label),
            'label' => $label,
            'accessKey' => $accessKey,
            'secretAccessKey' => $secretAccessKey,
            'writeAccess' => true,
            'aclRoles' => $roles,
            'customFields' => [
                'sendinblue' => 1
            ]
        ]], $context);

        $prefix = ConfigService::CONFIG_PREFIX;
        $this->systemConfigService->set($prefix . ConfigService::CONFIG_ACCESS_KEY, $accessKey, $salesChannelId);
        $this->systemConfigService->set($prefix . ConfigService::CONFIG_SECRET_ACCESS_KEY, $secretAccessKey, $salesChannelId);
        $this->systemConfigService->set($prefix . ConfigService::CONFIG_API_KEY, md5($accessKey . $secretAccessKey), $salesChannelId);
    }

    /**
     * @param string $label
     * @param Context $context
     */
    public function deleteIntegration(string $label, Context $context): void
    {
        $this->integrationRepository->delete([
            ['id' => md5($label)]
        ], $context);
    }

    /**
     * @param Context $context
     */
    public function deleteIntegrations(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.sendinblue', 1));
        $integrationIds = $this->integrationRepository->searchIds($criteria, $context)->getIds();
        if (empty($integrationIds)) {
            return;
        }

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $integrationIds);

        $this->integrationRepository->delete($ids, $context);
    }

    /**
     * @param Context $context
     * @return AclRoleEntity|null
     */
    private function getAclRole(Context $context): ?AclRoleEntity
    {
        $role = $this->aclRoleRepository->search(new Criteria([md5(NewsletterSendinblue::ACL_ROLE_NAME)]), $context)->first();
        if ($role instanceof AclRoleEntity) {
            return $role;
        }

        return $this->aclRoleRepository->search(new Criteria([md5(NewsletterSendinblue::OLD_ACL_ROLE_NAME)]), $context)->first();
    }
}
