<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Subscriber;

use League\Period\Exception;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\DAL\RentalCheapestPriceContainer;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceContainer;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RentalProduct implements EventSubscriberInterface, RentalProductModeInterface
{
    private readonly EntityRepository $rentalProductRepository;

    public function __construct(EntityRepository $rentalProductRepository)
    {
        $this->rentalProductRepository = $rentalProductRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'rental_product.written' => 'onRentalProductWritten',
            'order_line_item.loaded' => 'onOrderLineItemLoaded',
            'rental_product.loaded' => 'loaded',
        ];
    }

    /**
     * @throws Exception
     */
    public function onRentalProductWritten(EntityWrittenEvent $event): void
    {
        $writeResults = $event->getWriteResults();
        foreach ($writeResults as $writeResult) {
            $payload = $writeResult->getPayload();
            $rentalTimes = [];
            if (!empty($payload['blockedPeriods'])) {
                foreach ($payload['blockedPeriods'] as $blockedPeriod) {
                    $rentalTimes[] = RentalTime::createJson(
                        $payload['productId'],
                        $blockedPeriod['blocked_quantity'],
                        $blockedPeriod['rhiem_rental_products_rent_start'],
                        $blockedPeriod['rhiem_rental_products_rent_end'],
                        'block',
                        self::DAYRENT
                    );
                }

                $this->rentalProductRepository->upsert(
                    [
                        [
                            'id' => $payload['id'],
                            'rentalTimes' => $rentalTimes,
                        ],
                    ],
                    $event->getContext()
                );
            }
        }
    }

    public function onOrderLineItemLoaded(EntityLoadedEvent $event): void
    {
        /** @var OrderLineItemEntity $entity */
        foreach ($event->getEntities() as $entity) {
            $payload = $entity->getPayload();
            if (!empty($payload['rentalProduct']) && !empty($payload['rentalProduct']['rentalTime'])) {
                if (is_string($payload['rentalProduct']['rentalTime'])) {
                    $payload['rentalProduct']['rentalTime'] = unserialize($payload['rentalProduct']['rentalTime']);
                    $payload['rentalProduct']['rentalTimePayload'] = $payload['rentalProduct']['rentalTime']->toJson();
                } else if (is_array($payload['rentalProduct']['rentalTime'])) {
                    $payload['rentalProduct']['rentalTimePayload'] = $payload['rentalProduct']['rentalTime'];
                    $payload['rentalProduct']['rentalTime'] = RentalTime::fromJson($payload['rentalProduct']['rentalTime']);
                }

                $entity->setPayload($payload);
            }
        }
    }

    public function loaded(EntityLoadedEvent $event): void
    {
        /** @var RentalProductEntity $rentalProduct */
        foreach ($event->getEntities() as $rentalProduct) {
            $price = $rentalProduct->getCheapestPrice();
            if ($price instanceof CheapestPriceContainer) {
                $rentalPrice = new RentalCheapestPriceContainer($price);
                $resolved = $rentalPrice->resolve($event->getContext());
                $rentalProduct->setCheapestPriceContainer($price);
                $rentalProduct->setCheapestPrice($resolved);
            }
        }
    }
}
