<?php declare(strict_types=1);

namespace NewsletterSendinblue\Controller;

use NewsletterSendinblue\Service\ConfigService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class SendInBlueController extends StorefrontController
{

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var string
     */
    private $serviceWorkerPath;

    /**
     * @param ConfigService $configService
     * @param string $serviceWorkerPath
     */
    public function __construct(ConfigService $configService, string $serviceWorkerPath)
    {
        $this->configService = $configService;
        $this->serviceWorkerPath = $serviceWorkerPath;
    }

    /**
     * @Route("/sendinblue/service-worker.js", name="frontend.sendinblue.service-worker", methods={"GET"})
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return Response
     * @throws FileNotFoundException
     */
    public function serviceWorker(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $this->configService->setSalesChannelId($salesChannelContext->getSalesChannelId());

        $isPageTackingEnabled = $this->configService->isPageTrackingEnabled();
        $marketingAutomationClientKey = $this->configService->getMAKey();
        if (!$isPageTackingEnabled || !$marketingAutomationClientKey) {
            throw new FileNotFoundException('', 404);
        }
        if (!file_exists($this->serviceWorkerPath)) {
            throw new FileNotFoundException('', 404);
        }

        $response = new Response(file_get_contents($this->serviceWorkerPath));
        $response->headers->set('Content-Type', 'application/javascript');
        return $response;
    }

}