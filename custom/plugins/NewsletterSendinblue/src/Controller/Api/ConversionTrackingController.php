<?php

namespace NewsletterSendinblue\Controller\Api;

use NewsletterSendinblue\Service\ConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class ConversionTrackingController extends AbstractController
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * ConversionTrackingController constructor.
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @deprecated
     *
     * @Route(
     *     path="/api/v{version}/sendinblue/tracking",
     *     name="api.v.action.sendinblue.updateTracking",
     *     methods={"PUT"}
     *  )
     * @Route(
     *     path="/api/sendinblue/tracking",
     *     name="api.action.sendinblue.updateTracking",
     *     methods={"PUT"}
     *  )
     * @param Request $request
     * @param Context $context
     *
     * @return JsonResponse
     */
    public function updateConversionTracking(Request $request, Context $context)
    {
        $conversionTracking = $request->get(ConfigService::CONFIG_CONVERSION_TRACKING, false);
        $this->configService->setConversionTracking($conversionTracking);

        return new JsonResponse([ConfigService::CONFIG_CONVERSION_TRACKING => $conversionTracking]);
    }

    /**
     * @deprecated
     *
     * @Route(
     *     path="/api/v{version}/sendinblue/tracking",
     *     name="api.v.action.sendinblue.getTracking",
     *     methods={"GET"}
     * )
     * @Route(
     *     path="/api/sendinblue/tracking",
     *     name="api.action.sendinblue.getTracking",
     *     methods={"GET"}
     * )
     *
     * @param Request $request
     * @param Context $context
     *
     * @return JsonResponse
     */
    public function getConversionTracking(Request $request, Context $context) : JsonResponse
    {
         return new JsonResponse([
            ConfigService::CONFIG_CONVERSION_TRACKING => $this->configService->getConversionTracking()
        ]);
    }
}
