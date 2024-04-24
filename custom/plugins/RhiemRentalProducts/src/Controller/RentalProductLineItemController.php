<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Controller;

use League\Period\Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\LineItem\RentalProductLineItemFactory;
use Rhiem\RhiemRentalProducts\Components\RentalProductBail\LineItem\RentalProductBailLineItemFactory;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class RentalProductLineItemController extends StorefrontController
{
    private readonly CartService $cartService;

    private readonly RentalProductLineItemFactory $rentalProductLineItemFactory;

    private readonly RentalProductBailLineItemFactory $rentalProductBailLineItemFactory;

    public function __construct(
        CartService $cartService,
        RentalProductLineItemFactory $rentalProductLineItemFactory,
        RentalProductBailLineItemFactory $rentalProductBailLineItemFactory
    ) {
        $this->cartService = $cartService;
        $this->rentalProductLineItemFactory = $rentalProductLineItemFactory;
        $this->rentalProductBailLineItemFactory = $rentalProductBailLineItemFactory;
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/checkout/rental-line-item/add', name: 'frontend.checkout.rental-line-item.add', methods: ['POST'], defaults: ['XmlHttpRequest' => true])]
    public function addLineItems(
        Cart $cart,
        RequestDataBag $requestDataBag,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): Response {
        /** @var RequestDataBag|null $lineItems */
        $lineItems = $requestDataBag->get('lineItems');
        if (!$lineItems instanceof RequestDataBag) {
            throw new MissingRequestParameterException('lineItems');
        }

        $count = 0;
        $items = [];
        $rentalProducts = [];

        try {
            /** @var RequestDataBag $lineItemData */
            foreach ($lineItems as $lineItemData) {
                $item = $this->rentalProductLineItemFactory->create($lineItemData->all(), $salesChannelContext);
                $count += $item->getQuantity();
                $items[] = $item;
                if (!array_key_exists($item->getReferencedId(), $rentalProducts)) {
                    /* @var RentalProductEntity[] $rentalProducts */
                    $rentalProducts[$item->getReferencedId()] = $this->getRentalProduct(
                        $item->getReferencedId(),
                        $salesChannelContext->getContext()
                    );
                }

                $bailData = $rentalProducts[$item->getReferencedId()]->getBail();
                if (!empty($bailData) && $bailData['active']) {
                    $bailItem = $this->rentalProductBailLineItemFactory->create(
                        $lineItemData->all(),
                        $salesChannelContext
                    );
                    $items[] = $bailItem;
                }
            }

            $cart = $this->cartService->add($cart, $items, $salesChannelContext);
            if (!$this->traceErrors($cart)) {
                $this->addFlash('success', $this->trans('checkout.addToCartSuccess', ['%count%' => $count]));
            }
        } catch (ProductNotFoundException) {
            $this->addFlash('danger', $this->trans('error.addToCartError'));
        }

        return $this->createActionResponse($request);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/checkout/rental-line-item/change-date', name: 'frontend.checkout.rental-line-item.change-date', defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function changeDate(
        Cart $cart,
        RequestDataBag $requestDataBag,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): Response {
        $rentPeriod = $requestDataBag->get('rentPeriod');

        foreach ($cart->getLineItems() as $lineItem) {
            if($lineItem->getType() !== "rentalProduct") continue;

            $this->rentalProductLineItemFactory->addRentalTime($lineItem, $rentPeriod, $salesChannelContext->getContext());
        }

        $cart = $this->cartService->recalculate($cart, $salesChannelContext);

        if (!$this->traceErrors($cart)) {
            $this->addFlash('success', $this->trans('rhiem-rental-products.changeDateSuccess'));
        }

        return $this->createActionResponse($request);
    }

    private function traceErrors(Cart $cart): bool
    {
        if ($cart->getErrors()->count() <= 0) {
            return false;
        }

        $this->addCartErrorsToFlashBag($cart->getErrors()->getNotices(), 'info');
        $this->addCartErrorsToFlashBag($cart->getErrors()->getWarnings(), 'warning');
        $this->addCartErrorsToFlashBag($cart->getErrors()->getErrors(), 'danger');
        $cart->getErrors()->clear();

        return true;
    }

    /**
     * @param Error[] $errors
     */
    private function addCartErrorsToFlashBag(array $errors, string $type): void
    {
        foreach ($errors as $error) {
            $parameters = [];
            foreach ($error->getParameters() as $key => $value) {
                $parameters['%' . $key . '%'] = $value;
            }

            $message = $this->trans('checkout.' . $error->getMessageKey(), $parameters);
            $this->addFlash($type, $message);
        }
    }

    private function getRentalProduct(string $productId, ?Context $context = null): ?RentalProductEntity
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('productId', $productId))
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('product');

        $rentalProductRepository = $this->rentalProductLineItemFactory->getRentalProductRepository();

        return $rentalProductRepository->search($criteria, $context)->first();
    }
}
