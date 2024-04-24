<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FrontendListing implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'listingCriteriaCreation',
        ];
    }

    public function listingCriteriaCreation(ProductListingCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('rentalProduct');
        $criteria->addAssociation('rentalProduct.parent');
        $criteria->addAssociation('rentalProduct.children');
        $criteria->addAssociation('rentalProduct.prices');
        $criteria->addAssociation('rentalProduct.product');
    }
}
