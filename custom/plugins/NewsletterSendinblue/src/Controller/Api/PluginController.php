<?php

namespace NewsletterSendinblue\Controller\Api;

use NewsletterSendinblue\Service\VersionProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class PluginController extends AbstractController
{
    /**
     * @var VersionProvider
     */
    private $versionProvider;

    public function __construct(VersionProvider $versionProvider)
    {
        $this->versionProvider = $versionProvider;
    }

    /**
     * @Route("/api/v{version}/sendinblue/test", name="api.v.action.sendinblue.testConnection", methods={"GET"})
     * @Route("/api/sendinblue/test", name="api.action.sendinblue.testConnection", methods={"GET"})
     */
    public function testConnectionAction(): JsonResponse
    {
        return new JsonResponse([
            "success" => true,
            "plugin_version" => $this->versionProvider->getPluginVersion(),
            "shop_version" => $this->versionProvider->getShopwareVersion()
        ]);
    }

    /**
     * @Route("/api/v{version}/sendinblue/info", name="api.v.action.sendinblue.getPluginInfo", methods={"GET"})
     * @Route("/api/sendinblue/info", name="api.action.sendinblue.getPluginInfo", methods={"GET"})
     */
    public function getPluginInfoAction(): JsonResponse
    {
        $response = [];
        try {
            $response['success'] = true;
            $response['version'] = $this->versionProvider->getPluginVersion();
            $response['shop_version'] = $this->versionProvider->getShopwareVersion();
        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['version'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
}
