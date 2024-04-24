<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Controller;

use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Rhiem\RhiemRentalProducts\Components\Cart\CartHelper;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductQuantityCalculator;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CalendarController extends StorefrontController
{
    private readonly RentalProductQuantityCalculator $rentalProductQuantityCalculator;

    private readonly CartService $cartService;

    private readonly EntityRepository $rentalProductRepository;

    private readonly SystemConfigService $systemConfigService;

    private readonly CartHelper $cartHelper;

    public function __construct(
        RentalProductQuantityCalculator $rentalProductQuantityCalculator,
        CartService $cartService,
        EntityRepository $rentalProductRepository,
        SystemConfigService $systemConfigService,
        CartHelper $cartHelper
    ) {
        $this->rentalProductQuantityCalculator = $rentalProductQuantityCalculator;
        $this->cartService = $cartService;
        $this->rentalProductRepository = $rentalProductRepository;
        $this->systemConfigService = $systemConfigService;
        $this->cartHelper = $cartHelper;
    }

    /**
     *
     * @throws \Exception
     * @return Response
     */
    #[Route(path: '/rental-product/calendar/rent-data/{productId}', name: 'frontend.rentalproducts.calendar.rent.data', options: ['seo' => false], methods: ['GET'], defaults: ['XmlHttpRequest' => true])]
    public function getRentData(Request $request, Context $context, SalesChannelContext $salesChannelContext): JsonResponse
    {
        /** @var string $productId */
        $productId = $request->attributes->get('productId');
        $rentalProduct = $this->getRentalProduct($productId, $context);
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $rentTimesForProduct = $this->rentalProductQuantityCalculator->createRentTimesForProduct(
            $cart,
            $rentalProduct,
            $productId,
            $salesChannelContext->getContext()
        );

        $blockedDays = array_filter($rentTimesForProduct, static fn($value) => $value['minAvailable'] <= 0);

        $return = [
            'rentTimes' => $rentTimesForProduct,
            'blockedDays' => array_keys($blockedDays)
        ];

        if ($this->systemConfigService->get('RhiemRentalProducts.config.uniformRentalPeriods')) {
            $currentRentalTime = $this->cartHelper->getCurrentRentalTime($cart);

            if ($currentRentalTime instanceof RentalTime) {
                $return['currentRentalPeriods'] = [
                    "startDate" => $currentRentalTime->getStartDate()->format("m/d/Y"),
                    "endDate" => $currentRentalTime->getEndDate()->format("m/d/Y")
                ];
            }
        }

        return new JsonResponse($return);
    }

    private function getRentalProduct(string $productId, Context $context): ?RentalProductEntity
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('productId', $productId))
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('product');

        return $this->rentalProductRepository->search($criteria, $context)->first();
    }
}
