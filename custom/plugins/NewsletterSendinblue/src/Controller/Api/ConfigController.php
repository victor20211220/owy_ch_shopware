<?php

namespace NewsletterSendinblue\Controller\Api;

use NewsletterSendinblue\Service\ConfigService;
use NewsletterSendinblue\Traits\HelperTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route(defaults: ['_routeScope' => ['api']])]
class ConfigController extends AbstractController
{
    use HelperTrait;

    /**
     * @var ConfigService
     */
    private $configService;
    
    /**
     * @var EntityRepository
     */
    private $systemConfigRepository;

    /**
     * @param ConfigService $configService
     * @param EntityRepository $systemConfigRepository
     */
    public function __construct(ConfigService $configService, EntityRepository $systemConfigRepository)
    {
        $this->configService = $configService;
        $this->systemConfigRepository = $systemConfigRepository; 
    }

    /**
     * @Route(
     *     path="/api/v{version}/sendinblue/configs",
     *     name="api.v.action.sendinblue.config",
     *     methods={"PUT"},
     *     defaults={"auth_required"=false}
     * )
     * @Route(
     *     path="/api/sendinblue/configs",
     *     name="api.action.sendinblue.config",
     *     methods={"PUT"},
     *     defaults={"auth_required"=false}
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateConfigs(Request $request): JsonResponse
    {
        $userConnectionId = $request->request->get('userConnectionId');
        $salesChannelId = $this->getSalesChannelIdByConnectionId($userConnectionId);
        $this->configService->setSalesChannelId($salesChannelId, false);

        $this->configService->updateSendinblueConfigs($request->request->all());

        return new JsonResponse(['status' => 200]);
    }

    /**
     * @Route(
     *     path="/api/v{version}/sendinblue/disconnect",
     *     name="api.v.action.sendinblue.disconnect",
     *     methods={"POST"},
     *     defaults={"auth_required"=false}
     * )
     * @Route(
     *     path="/api/sendinblue/disconnect",
     *     name="api.action.sendinblue.disconnect",
     *     methods={"POST"},
     *     defaults={"auth_required"=false}
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteConfigs(Request $request): JsonResponse
    {
        $userConnectionId = $request->request->get('userConnectionId');
        $salesChannelId = $this->getSalesChannelIdByConnectionId($userConnectionId);
        $this->configService->setSalesChannelId($salesChannelId, false);

        $configs = [
            $this->configService::CONFIG_MA_KEY,
            $this->configService::CONFIG_USER_CONNECTION_ID,
            $this->configService::CONFIG_AUTH_KEY,
            $this->configService::CONFIG_ACCESS_TOKEN,
            $this->configService::CONFIG_REFRESH_TOKEN,
            $this->configService::CONFIG_IS_FULL_CUSTOMER_SYNC_ENABLED,
            $this->configService::CONFIG_CONVERSION_TRACKING,
            $this->configService::CONFIG_IS_AUTO_SYNC_ENABLED,
            $this->configService::CONFIG_IS_CONTACT_STATE_SYNC_ENABLED,
            $this->configService::CONFIG_SUBSCRIPTION_MAILING,
            $this->configService::CONFIG_SUBSCRIPTION_MAILING_TYPE,
            $this->configService::CONFIG_IS_PAGE_TRACKING_ENABLED,
            $this->configService::CONFIG_IS_ABANDONED_CART_TRACKING_ENABLED,
            $this->configService::CONFIG_MAPPED_GROUPS,
            $this->configService::CONFIG_IS_SMTP_ENABLED,
            $this->configService::CONFIG_SMTP_SENDER,
            $this->configService::CONFIG_SMTP_USER,
            $this->configService::CONFIG_SMTP_PASSWORD,
            $this->configService::CONFIG_SMTP_HOST,
            $this->configService::CONFIG_SMTP_PORT,
        ];

        if ($this->configService->isSmtpEnabled()) {
            $this->configService->disableSmtp();
        }

        foreach ($configs as $config) {
            $this->configService->deleteSendinblueConfig($config);
        }

        return new JsonResponse(['status' => 200]);
    }
}
