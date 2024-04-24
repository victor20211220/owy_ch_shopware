<?php declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Extension\Content\Product;

use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToOneAssociationField(
                'rentalProduct',
                'id',
                'product_id',
                RentalProductDefinition::class,
                true
            )
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}
