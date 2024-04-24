<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderDeliveryExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                'acrisOrderDeliveryStore',
                'id',
                'order_delivery_id',
                OrderDeliveryStoreDefinition::class, true)
            )->addFlags(new ApiAware(), new CascadeDelete())
        );
    }

    public function getDefinitionClass(): string
    {
        return OrderDeliveryDefinition::class;
    }
}
