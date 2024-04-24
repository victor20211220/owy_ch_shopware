<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProductBail\LineItem;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\LineItemFactoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RentalProductBailLineItemFactory implements LineItemFactoryInterface
{
    /**
     * @var string
     */
    final public const TYPE = 'rentalProductBail';

    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function create(array $data, SalesChannelContext $context): LineItem
    {
        $hash = md5((string) $data['rentPeriod']);
        $id = $data['id'] . '.rental.' . $hash . '.bail';

        $lineItem = new LineItem(
            $id,
            self::TYPE,
            $data['referencedId'],
            1
        );
        $lineItem->markModified();
        $lineItem->setRemovable(false);
        $lineItem->setStackable(false);
        $lineItem->setLabel('Bail');
        $lineItem->setGood(true);

        return $lineItem;
    }

    public function update(LineItem $lineItem, array $data, SalesChannelContext $context): void
    {
    }
}
