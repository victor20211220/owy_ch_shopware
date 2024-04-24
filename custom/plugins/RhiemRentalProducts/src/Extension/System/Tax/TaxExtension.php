<?php declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Extension\System\Tax;

use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ReverseInherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Tax\TaxDefinition;

class TaxExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField('rentalProduct', RentalProductDefinition::class, 'tax_id', 'id'))->addFlags(new RestrictDelete(), new ReverseInherited('tax'))
        );
        $collection->add(
            (new OneToManyAssociationField('rentalProduct', RentalProductDefinition::class, 'bail_tax_id', 'id'))->addFlags(new RestrictDelete(), new ReverseInherited('bailtax'))
        );
    }

    public function getDefinitionClass(): string
    {
        return TaxDefinition::class;
    }
}
