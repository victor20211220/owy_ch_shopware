<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Entities\RentalProductPrice;

use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ReverseInherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class RentalProductPriceDefinition extends EntityDefinition
{
    /**
     * @var string
     */
    final public const ENTITY_NAME = 'rental_product_price';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return RentalProductPriceCollection::class;
    }

    public function getEntityClass(): string
    {
        return RentalProductPriceEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new VersionField(),
            (new FkField('rental_product_id', 'rentalProductId', RentalProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(RentalProductDefinition::class))->addFlags(new Required()),
            (new FkField('rule_id', 'ruleId', RuleDefinition::class))->addFlags(new Required()),
            (new PriceField('price', 'price'))->addFlags(new Required()),
            (new IntField('quantity_start', 'quantityStart'))->addFlags(new Required()),
            new IntField('quantity_end', 'quantityEnd'),
            (new IntField('mode', 'mode'))->addFlags(new Required()),
            (new ManyToOneAssociationField('rentalProduct', 'rental_product_id', RentalProductDefinition::class, 'id', false))->addFlags(new ReverseInherited('prices')),
            (new ManyToOneAssociationField('rule', 'rule_id', RuleDefinition::class, 'id', false)),
        ]);
    }
}
