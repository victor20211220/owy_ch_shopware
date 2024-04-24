<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Controller;

use Acris\StoreLocator\Components\Locator;
use Acris\StoreLocator\Components\StoreLocatorService;
use Acris\StoreLocator\Core\Content\StoreLocator\Exception\StoreGroupIdNotFoundException;
use Acris\StoreLocator\Core\Content\StoreLocator\Exception\StoreIdNotFoundException;
use Acris\StoreLocator\Core\Content\StoreLocator\Exception\StorePageNotFoundException;
use Acris\StoreLocator\Storefront\Page\AcrisStoreGroup\StoreGroupPageLoader;
use Acris\StoreLocator\Storefront\Page\AcrisStoreLocator\StoreLocatorPageLoader;
use Acris\StoreLocator\Storefront\Page\AcrisStoreLocator\StoreLocatorSelectionPageLoader;
use Acris\StoreLocator\Storefront\Page\AcrisStoreLocatorDetail\StoreLocatorDetailPageLoader;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class StoreLocatorController extends StorefrontController
{
    public const STORE_LOCATOR_STORE_SELECTION_ACCOUNT_KEY = 'acrisStoreLocatorStore';
    const DEFAULT_REDIRECT_ROUTE = 'frontend.account.home.page';

    public function __construct(
        private Locator                         $locator,
        private StoreLocatorPageLoader          $storeLocatorPageLoader,
        private StoreGroupPageLoader            $storeGroupPageLoader,
        private StoreLocatorDetailPageLoader    $storeLocatorDetailPageLoader,
        private StoreLocatorSelectionPageLoader $storeLocatorSelectionPageLoader,
        private StoreLocatorService             $storeLocatorService,
    )
    {
    }

    #[Route(path: '/store-locator', name: 'frontend.storeLocator.index', methods: ['GET'], defaults: ['XmlHttpRequest' => true], options: ['seo' => 'false'])]
    public function index(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        try {
            $page = $this->storeLocatorPageLoader->load($request, $salesChannelContext);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false]);
        }
        if ($page) {
            return $this->renderStorefront('@Storefront/storefront/base.html.twig', ['page' => $page]);
        }

        return new JsonResponse([
            'success' => false
        ]);
    }

    #[Route(path: '/store-locator/{groupId}/store-group', name: 'frontend.storeLocator.storeGroup', methods: ['GET'], defaults: ['XmlHttpRequest' => true], options: ['seo' => 'false'])]
    public function storeGroupIndex(?string $groupId, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if (!$groupId) {
            throw new StoreGroupIdNotFoundException();
        }

        $page = $this->storeGroupPageLoader->load($groupId, $request, $salesChannelContext);

        if ($page) {
            return $this->renderStorefront('@Storefront/storefront/base.html.twig', ['page' => $page]);
        }
        return new JsonResponse([
            'success' => false
        ]);
    }


    #[Route(path: '/store-locator/get-store-information', name: 'frontend.storeLocator.getStoreInformation', methods: ['POST','GET'], defaults: ['XmlHttpRequest' => true], options: ['seo' => 'false'])]
    public function getStoreInformation(Request $request, SalesChannelContext $context): Response
    {
        $city = $request->request->get('city');
        $distance = $request->request->get('distance');
        $lat = $request->request->get('lat');
        $lng = $request->request->get('lng');
        $handlerpoints = $request->request->get('handlerpoints');




        if ($city === "" && $distance === "0" || $distance === "0") {
            $stores = $this->container->get('acris_store_locator.repository')
                ->search((new Criteria())
                    ->addFilter(new EqualsFilter('active', 1))
                    ->addAssociation('country')
                    ->addAssociation('storeGroup')
                    ->addAssociation('storeGroup.icon')
                    ->addAssociation('storeGroup.media')
                    ->addFilter(
                        new OrFilter([
                            new EqualsFilter('salesChannels.id', null),
                            new EqualsFilter('salesChannels.id', $context->getSalesChannelId())
                        ]))
                    , $context->getContext());
            if (sizeof($stores) !== 0) {
                return new JsonResponse(['success' => true, 'data' => $stores]);
            } else {
                return new JsonResponse(['success' => false, 'error' => "no data"]);
            }
        } else {
            if ($lat && $lng) {
                $latitude = $lat;
                $longitude = $lng;


                $data = $this->locator->locateDataByDistance($latitude, $longitude, $distance, $handlerpoints, $context->getContext());
                if ($data === "no data") {
                    return new JsonResponse(['success' => false, 'error' => $data]);
                } else {
                    return new JsonResponse(['success' => true, 'data' => $data]);
                }
            } else {
                return new JsonResponse(['success' => false, 'error' => "no permission"]);
            }
        }
    }

    #[Route(path: '/store-locator/detail/{storeId}', name: 'frontend.storeLocator.detail', methods: ['GET'], defaults: ['XmlHttpRequest' => true], options: ['seo' => 'false'])]
    public function detail(string $storeId, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if (!$storeId) {
            throw new StoreIdNotFoundException();
        }

        $page = $this->storeLocatorDetailPageLoader->load($storeId, $request, $salesChannelContext);

        if ($page) {
            return $this->renderStorefront('@Storefront/storefront/base.html.twig', ['page' => $page]);
        }

        throw new StorePageNotFoundException();

    }

    #[Route(path: '/store-locator/select-store', name: 'frontend.storeLocator.selectStore', methods: ['POST', 'GET'], defaults: ['XmlHttpRequest' => true], options: ['seo' => 'false'])]
    public function selectStore(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $page = $this->storeLocatorSelectionPageLoader->load($request, $salesChannelContext);

        return $this->renderStorefront('@Storefront/storefront/page/store-locator/account/index.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route(path: '/store-locator/select-store/save', name: 'frontend.storeLocator.selectStore.save', methods: ['POST'], defaults: ['XmlHttpRequest' => true])]
    public function saveStoreSelection(RequestDataBag $data, SalesChannelContext $context): Response
    {
        $storeId = $data->get(self::STORE_LOCATOR_STORE_SELECTION_ACCOUNT_KEY);

        if (empty($storeId)) throw new MissingRequestParameterException('storeId');

        try {
            $this->storeLocatorService->assignStoreToCustomer($storeId, $context);
            $this->addFlash(self::SUCCESS, $this->trans('acrisStoreLocator.account.storeAssignedSuccess'));
        } catch (\Throwable $exception) {
            $this->addFlash(self::DANGER, $this->trans('acrisStoreLocator.account.storeAssignedError') . $exception->getMessage());
        }

        return $this->redirectToRoute(self::DEFAULT_REDIRECT_ROUTE);
    }
}
