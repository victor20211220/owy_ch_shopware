<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\Order;

use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductCartChangedException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductQuantityReduceException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductRemoveException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\LineItem\RentalProductLineItemFactory;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductQuantityCalculator;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataValidationFactoryInterface;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class OrderServiceDecorator extends OrderService
{
    private readonly OrderService $orderService;

    private readonly CartService $cartService;

    private readonly EntityRepository $rentalProductRepository;

    private readonly RentalProductQuantityCalculator $rentalProductQuantityCalculator;

    private readonly ContainerInterface $container;

    private readonly iterable $orderValidator;

    private readonly SystemConfigService $systemConfigService;

    public function __construct(
        OrderService $orderService,
        EntityRepository $rentalProductRepository,
        RentalProductQuantityCalculator $rentalProductQuantityCalculator,
        ContainerInterface $container,
        iterable $orderValidator,
        DataValidator $dataValidator,
        DataValidationFactoryInterface $orderValidationFactory,
        EventDispatcherInterface $eventDispatcher,
        CartService $cartService,
        EntityRepository $paymentMethodRepository,
        StateMachineRegistry $stateMachineRegistry,
        SystemConfigService $systemConfigService
    ) {
        $this->orderService = $orderService;
        $this->rentalProductRepository = $rentalProductRepository;
        $this->rentalProductQuantityCalculator = $rentalProductQuantityCalculator;
        $this->cartService = $cartService;
        $this->container = $container;
        $this->orderValidator = $orderValidator;
        $this->systemConfigService = $systemConfigService;

        parent::__construct(
            $dataValidator,
            $orderValidationFactory,
            $eventDispatcher,
            $cartService,
            $paymentMethodRepository,
            $stateMachineRegistry
        );
    }

    /**
     * @throws \Exception
     */
    public function createOrder(DataBag $data, SalesChannelContext $context): string
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);
        $lineItems = $cart->getLineItems()->filterFlatByType(RentalProductLineItemFactory::TYPE);
        if ($lineItems !== []) {
            $checkForRentalTerms = $this->systemConfigService->get('RhiemRentalProducts.config.showRentalTerms');
            if ($checkForRentalTerms) {
                $this->checkForAcceptedRentalTerms($data);
            }

            $rentalProducts = [];
            foreach ($lineItems as $lineItem) {
                $criteria = new Criteria();
                $criteria
                    ->addFilter(new EqualsFilter('productId', $lineItem->getReferencedId()))
                    ->addFilter(new EqualsFilter('active', true))
                    ->addAssociation('product');
                $rentalProducts[$lineItem->getReferencedId()] = $this->rentalProductRepository->search(
                    $criteria,
                    $context->getContext()
                )->first();
                if (!$rentalProducts[$lineItem->getReferencedId()] instanceof RentalProductEntity) {
                    $cart->remove($lineItem->getId());
                    /** @var IdentityTranslator $translator */
                    $translator = $this->container->get('translator');
                    $message = $translator->trans('error.addToCartError');
                    /** @var Session $session */
                    $session = $this->container->get('session');
                    $session->getFlashBag()->add('danger', $message);
                }
            }

            $this->rentalProductQuantityCalculator->calculateCartQuantities(
                $cart,
                $lineItems,
                $rentalProducts,
                $context
            );
        }

        foreach ($this->orderValidator as $orderValidator) {
            /* @var Validator $orderValidator */
            $orderValidator->validate(
                [
                    'cart' => $cart,
                    'rentalProductRepository' => $this->rentalProductRepository,
                    'context' => $context->getContext(),
                ]
            );
        }

        if (!empty($cart->getErrors()->getElements())) {
            $this->cartService->recalculate($cart, $context);
            $violation = [];
            foreach ($cart->getErrors()->getElements() as $error) {
                if ($error instanceof RentalProductQuantityReduceException
                    || $error instanceof RentalProductRemoveException
                    || $error instanceof RentalProductCartChangedException) {
                    /* @var RentalProductQuantityReduceException|RentalProductRemoveException $error */
                    if ($cart->getLineItems()->count() === 0) {
                        /** @var IdentityTranslator $translator */
                        $translator = $this->container->get('translator');
                        $message = $translator->trans('error.' . $error->getMessageKey(), $error->getParameters());
                        /** @var Session $session */
                        $session = $this->container->get('session');
                        $session->getFlashBag()->add('danger', $message);
                    }

                    $errorName = '';
                    if (method_exists($error, 'getName')) {
                        $errorName = $error->getName();
                    }

                    $violation[] = $this->createNewViolation($errorName, $error->getMessageKey());
                }
            }

            if ($violation !== []) {
                $violationList = new ConstraintViolationList($violation);

                throw new ConstraintViolationException($violationList, []);
            }
        }

        return $this->orderService->createOrder($data, $context);
    }

    private function checkForAcceptedRentalTerms(DataBag $data): void
    {
        $violation = [];
        if ($data->get('rentalTerms') !== 'on') {
            $violation[] = $this->createNewViolation('rentalTerms', 'checkout-rental-terms-unchecked');

            $violationList = new ConstraintViolationList($violation);

            throw new ConstraintViolationException($violationList, []);
        }
    }

    private function createNewViolation(string $errorPath, string $errorCode): ConstraintViolation
    {
        return new ConstraintViolation(
            '',
            '',
            [],
            '',
            '/' . $errorPath,
            '',
            null,
            $errorCode
        );
    }
}
