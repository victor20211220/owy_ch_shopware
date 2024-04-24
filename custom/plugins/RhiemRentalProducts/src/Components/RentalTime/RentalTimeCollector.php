<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalTime;

use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\LineItem\RentalProductLineItemFactory;

class RentalTimeCollector implements RentalProductModeInterface
{
    private readonly EntityRepository $orderLineItemRepository;

    private readonly EntityRepository $rentalProductRepository;

    private readonly EntityRepository $stateRepository;

    public function __construct(
        EntityRepository $orderLineItemRepository,
        EntityRepository $rentalProductRepository,
        EntityRepository $stateRepository
    ) {
        $this->orderLineItemRepository = $orderLineItemRepository;
        $this->rentalProductRepository = $rentalProductRepository;
        $this->stateRepository = $stateRepository;
    }

    /**
     * @throws \Exception
     *
     * @return RentalTimeCollection[]
     */
    public static function createRentalTimeCollection(array $data): array
    {
        /**
         * @var RentalTimeCollection[] $collection
         */
        $collection = [];
        foreach ($data as $item) {
            switch ($item::class) {
                case LineItem::class:
                    $payload = $item->getPayloadValue('rentalProduct');
                    /** @var RentalTime $rentalTime */
                    $rentalTime = $payload['rentalTime'];
                    $productId = $item->getReferencedId();

                    break;
                case OrderLineItemEntity::class:
                    $orderPayload = $item->getPayload();
                    $payload = $orderPayload['rentalProduct'];
                    /** @var RentalTime $rentalTime */
                    $rentalTime = $payload['rentalTime'];

                    if(!$rentalTime instanceof RentalTime) {
                        $payload['rentalTime'] = RentalTime::createRentalTime(
                            $item->getReferencedId(),
                            $item->getQuantity(),
                            $payload['rentalTimePayload']["startDate"],
                            $payload['rentalTimePayload']['endDate'],
                            'rent',
                            1
                        );

                        $rentalTime = $payload['rentalTime'];
                        $item->setPayload($payload);
                    }

                    $rentalTime->setType('order');
                    $productId = $item->getReferencedId();

                    break;
                case RentalTime::class:
                    /** @var RentalTime $rentalTime */
                    $rentalTime = $item;
                    $productId = $rentalTime->getProductId();

                    break;
                default:
                    throw new \Exception('Unsupported Data Type');
            }

            if (!\array_key_exists($productId, $collection)) {
                $collection = RentalTimeCollection::createRentalTimeCollection(
                    $productId,
                    [$rentalTime]
                );
            } else {
                $collection[$productId]->add($rentalTime);
            }
        }

        return $collection;
    }

    /**
     * @return array
     */
    protected function collectDataFromOrders(string $productId, string $rentStart, string $rentEnd, Context $context)
    {
        $stateCriteria = new Criteria();
        $stateCriteria
            ->addAssociation('stateMachine')
            ->addFilter(new EqualsFilter('stateMachine.technicalName', OrderStates::STATE_MACHINE))
            ->addFilter(new EqualsFilter('technicalName', OrderStates::STATE_CANCELLED))
        ;
        $orderCancelledStateId = $this->stateRepository->search($stateCriteria, $context)->first()->getId();

        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('productId', $productId))
            ->addFilter(
                new RangeFilter('payload.rentalProduct.rentalTime.endDate', [RangeFilter::GTE => $rentStart])
            )
            ->addFilter(
                new RangeFilter('payload.rentalProduct.rentalTime.startDate', [RangeFilter::LTE => $rentEnd])
            )
            ->addAssociation('order')
            ->addFilter(new NotFilter('AND', [
                new EqualsFilter('order.stateId', $orderCancelledStateId),
            ]))
            ->addSorting(new FieldSorting('payload.rentalProduct.rentalTime.startDate'));

            $result = $this->orderLineItemRepository->search($criteria, $context)->getElements();

        return $result;
    }

    /**
     * @return LineItemCollection
     */
    protected function collectDataFromCart(Cart $cart, string $productId)
    {
        $rentalLineItems = $cart->getLineItems()->filterType(RentalProductLineItemFactory::TYPE);

        return $rentalLineItems->filter(
            static fn(LineItem $cartLineItem) => $cartLineItem->getReferencedId() === $productId
        );
    }

    protected function collectDataFromBlockedPeriods(string $productId, Context $context): array
    {
        $criteria = new Criteria();

        $criteria
            ->addFilter(new EqualsFilter('productId', $productId))
            ->addFilter(new EqualsFilter('active', true));

        /** @var RentalProductEntity $rentalProduct */
        $rentalProduct = $this->rentalProductRepository->search(
            $criteria,
            $context
        )->first();

        $rentalTimeCollection = [];
        $rentalTimes = $rentalProduct->getRentalTimes();

        if (!empty($rentalTimes)) {
            foreach ($rentalTimes as $rentalTime) {
                $rentalTimeCollection[] = RentalTime::fromJson($rentalTime);
            }
        }

        return $rentalTimeCollection;
    }
}
