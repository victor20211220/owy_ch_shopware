<?php

namespace NewsletterSendinblue\Controller\Api;

use NewsletterSendinblue\Service\ConfigService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class CallbackController extends AbstractController
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @Route(
     *     "/api/v{version}/sendinblue/callback",
     *     name="api.v.action.sendinblue.callback",
     *     methods={"POST"},
     *     defaults={"auth_required"=false}
     * )
     * @Route(
     *     "/api/sendinblue/callback",
     *     name="api.action.sendinblue.callback",
     *     methods={"POST"},
     *     defaults={"auth_required"=false}
     * )
     *
     * @param Request $request
     * @param Context $context
     *
     * @return JsonResponse
    */
    public function authAction(Request $request, Context $context): Response
    {
        $apiKey = $request->get('apiKey');
        $salesChannelId = $request->get('sid');
        $userConnectionId = $request->get('user_connection_id');

        $this->configService->setSalesChannelId($salesChannelId, false);
        if ($this->configService->getApiKey() === $apiKey) {
            $this->configService->setUserConnectionId($userConnectionId);

            $response = ['success' => true];
        } else {
            $response = [
                'success' => false,
                'error' => 'API key is invalid'
            ];
        }

        return new JsonResponse($response);
    }
}
