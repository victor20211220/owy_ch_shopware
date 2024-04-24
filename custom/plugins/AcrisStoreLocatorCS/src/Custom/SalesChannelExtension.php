<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Acris\StoreLocator\Custom\Aggregate\StoreLocatorSalesChannelDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class SalesChannelExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'acrisStoreLocator',
                StoreLocatorDefinition::class,
                StoreLocatorSalesChannelDefinition::class,
                'sales_channel_id',
                'store_locator_id'
            ))
        );
    }

    public function getDefinitionClass(): string
    {
        return SalesChannelDefinition::class;
    }
}
