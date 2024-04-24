<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Subscriber;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Connection;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductPriceCalculator;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Product implements EventSubscriberInterface
{
    private readonly RentalProductPriceCalculator $rentalProductPriceCalculator;

    private readonly Connection $connection;

    private readonly RequestStack $requestStack;

    public function __construct(
        RentalProductPriceCalculator $rentalProductPriceCalculator,
        Connection $connection,
        RequestStack $requestStack
    ) {
        $this->rentalProductPriceCalculator = $rentalProductPriceCalculator;
        $this->connection = $connection;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array|string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_SEARCH_RESULT_LOADED_EVENT => 'onProductsLoaded',
        ];
    }

    public function onProductsLoaded(EntitySearchResultLoadedEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request instanceof Request) {
            return;
        }

        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (!$salesChannelContext instanceof SalesChannelContext) {
            return;
        }

        $products = $event->getResult()->getEntities();
        $parentIds = [];

        foreach ($products as $product) {
            $rentalProduct = $product->getExtension('rentalProduct');
            if (!empty($rentalProduct) && !empty($rentalProduct->getParentId())) {
                $parentIds[] = $rentalProduct->getParentId();
            }
        }

        if ($parentIds !== []) {
            $sql = 'SELECT LOWER(HEX(t1.parent_id)) as parent_id,MAX(IFNULL(t1.active,t2.active)) as hasRentalVariant FROM rental_product t1
                    LEFT JOIN rental_product t2 ON t1.parent_id=t2.id
                    WHERE HEX(t1.parent_id) IN(?)
                    GROUP BY t1.parent_id';
            $result = $this->connection->fetchAllAssociative(
                $sql,
                [$parentIds],
                [Connection::PARAM_STR_ARRAY]
            );
            $hasRentalVariant = [];
            foreach ($result as $row) {
                $hasRentalVariant[$row['parent_id']] = $row['hasRentalVariant'];
            }
        }

        foreach ($products as $product) {
            if (!$product instanceof SalesChannelProductEntity) {
                continue;
            }
            
            /**
             * @var RentalProductEntity $rentalProduct
             */
            $rentalProduct = $product->getExtension('rentalProduct');

            if (empty($rentalProduct)) {
                continue;
            }

            if ($rentalProduct->isActive() || !empty($hasRentalVariant[$rentalProduct->getParentId()])) {
                /*
                 * @var SalesChannelProductEntity $product
                 */
                $rentalProduct->addExtension('isRentalProduct', new ArrayStruct(['isRentalProduct' => true]));

                if ($rentalProduct->isActive()) {
                    $prices = $rentalProduct->getPrices();
                    if ($prices->count() !== 0) {
                        $priceMode = $prices->first()->getMode();
                        $rentalProduct->addExtension('rentalPriceMode', new ArrayStruct([$priceMode]));
                    }
                }

                $this->rentalProductPriceCalculator->calculateRentalProductPrices(
                    $salesChannelContext,
                    $product
                );
            }
        }
    }
}
