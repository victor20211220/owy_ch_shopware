<?php

namespace NewsletterSendinblue\Controller;

use NewsletterSendinblue\NewsletterSendinblue;
use NewsletterSendinblue\Service\ConfigService;
use NewsletterSendinblue\Service\IntegrationService;
use NewsletterSendinblue\Service\VersionProvider;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('administration')]
class BackendController extends AbstractController
{
    private $ref = '0258fe1ea464530db1028ba01531f151';

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var IntegrationService
     */
    private $integrationService;

    /**
     * @var EntityRepository
     */
    private $salesChannelRepository;

    /**
     * @var VersionProvider
     */
    private $versionProvider;

    /**
     * @param ConfigService $configService
     * @param IntegrationService $integrationService
     * @param EntityRepository $salesChannelRepository
     * @param VersionProvider $versionProvider
     */
    public function __construct(
        ConfigService             $configService,
        IntegrationService        $integrationService,
        EntityRepository $salesChannelRepository,
        VersionProvider           $versionProvider
    )
    {
        $this->configService = $configService;
        $this->integrationService = $integrationService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->versionProvider = $versionProvider;
    }

    /**
     * @Route(
     *     path="/api/v{version}/sendinblue/settings",
     *     name="api.v.action.sendinblue.settings",
     *     methods={"GET"}
     * )
     * @Route(
     *     path="/api/sendinblue/settings",
     *     name="api.action.sendinblue.settings",
     *     methods={"GET"}
     * )
     *
     * @param Request $request
     * @param Context $context
     *
     * @return JsonResponse
     */
    public function getUserConnectionSettingUrl(Request $request, Context $context): JsonResponse
    {
        $salesChannelId = $request->query->get('sid');
        $this->configService->setSalesChannelId($salesChannelId, false);

        $userConnectionId = $this->configService->getUserConnectionId();
        $baseUrl = sprintf('%s%s', $this->configService->getSendinblueFrontEndUrl(), '/integrations');
        $shopUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();

        if (!empty($userConnectionId)) {
            $response = [
                'success' => true,
                'connected' => true,
                'link' => sprintf('%s/%s/settings', $baseUrl, $userConnectionId)
            ];
        } else {
            $configs = $this->configService->getConfigs();
            if (empty($configs)
                || empty($configs[ConfigService::prepareConfigName(ConfigService::CONFIG_API_KEY)])
                || empty($configs[ConfigService::prepareConfigName(ConfigService::CONFIG_ACCESS_KEY)])
                || empty($configs[ConfigService::prepareConfigName(ConfigService::CONFIG_SECRET_ACCESS_KEY)])) {
                $integrationLabel = NewsletterSendinblue::INTEGRATION_LABEL;
                if ($salesChannelName = $this->getSalesChannelName($salesChannelId)) {
                    $integrationLabel .= ' - ' . $salesChannelName;
                }
                $this->integrationService->createIntegration($integrationLabel, $context, $salesChannelId);
            }
            $response = [
                'success' => true,
                'connected' => false,
                'link' => sprintf(
                    '%s/connect/SW6?%s',
                    $baseUrl,
                    http_build_query($this->getConnectorUrlParams($shopUrl, $salesChannelId))
                )
            ];
        }

        return new JsonResponse($response);
    }

    /**
     * @param string $host
     * @param string|null $salesChannelId
     *
     * @return array
     */
    private function getConnectorUrlParams(string $host, ?string $salesChannelId = null): array
    {
        return [
            'url' => $host,
            'ref' => $this->ref,
            'subshop' => false,
            'subshop_name' => $this->getSalesChannelName($salesChannelId),
            'shop_version' => $this->versionProvider->getShopwareVersion(),
            'pluginVersion' => $this->versionProvider->getPluginVersion(),
            'callback' => $this->createCallbackUrl($host, $salesChannelId),
            ConfigService::CONFIG_ACCESS_KEY => $this->configService->getAccessKey(),
            ConfigService::CONFIG_SECRET_ACCESS_KEY => $this->configService->getSecretAccessKey(),
            'utm_medium' => 'plugin',
            'utm_source' => 'shopware_6_plugin'
        ];
    }

    /**
     * @param string|null $salesChannelId
     * @return string
     */
    private function getSalesChannelName(?string $salesChannelId = null): string
    {
        $name = '';
        if (!empty($salesChannelId)) {
            $salesChannel = $this->getSalesChannel($salesChannelId);
            if (!empty($salesChannel)) {
                $name = $salesChannel->getTranslated()['name'];
            }
        }
        return $name;
    }

    /**
     * @param string $host
     * @param string|null $salesChannelId
     * @return string
     */
    private function createCallbackUrl(string $host, ?string $salesChannelId = null): string
    {
        $url = $host . '/api/' .
            (!$this->versionProvider->checkShopwareComptability() ? ('v' . PlatformRequest::API_VERSION . '/') : '') .
            'sendinblue/callback?' . ConfigService::CONFIG_API_KEY . '=' . $this->configService->getApiKey();
        if (!empty($salesChannelId)) {
            $url .= '&sid=' . $salesChannelId;
        }
        return $url;
    }

    /**
     * @param string $salesChannelId
     * @return SalesChannelEntity|null
     */
    private function getSalesChannel(string $salesChannelId): ?SalesChannelEntity
    {
        return $this->salesChannelRepository->search(new Criteria([$salesChannelId]), Context::createDefaultContext())->first();
    }
}
