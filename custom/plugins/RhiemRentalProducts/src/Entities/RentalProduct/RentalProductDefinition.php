<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Entities\RentalProduct;

use Rhiem\RhiemRentalProducts\Entities\RentalProductDepositPrice\RentalProductDepositPriceDefinition;
use Rhiem\RhiemRentalProducts\Entities\RentalProductPrice\RentalProductPriceDefinition;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceField;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ChildCountField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Tax\TaxDefinition;

class RentalProductDefinition extends EntityDefinition
{
    /**
     * @var string
     */
    final public const ENTITY_NAME = 'rental_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return RentalProductCollection::class;
    }

    public function getEntityClass(): string
    {
        return RentalProductEntity::class;
    }

    public function isInheritanceAware(): bool
    {
        return true;
    }

    public function getDefaults(): array
    {
        return [
            'mode' => 1,
            'active' => false,
            'purchasable' => false,
            'originalStock' => 0,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new VersionField(),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class, 'product_version_id'))->addFlags(new Required()),

            new ParentFkField(self::class),
            new ReferenceVersionField(self::class, 'parent_version_id'),
            new ParentAssociationField(self::class, 'id'),
            new ChildrenAssociationField(self::class),

            (new FkField('tax_id', 'taxId', TaxDefinition::class))->addFlags(new Inherited()),

            (new ManyToOneAssociationField('tax', 'tax_id', TaxDefinition::class, 'id', true))->addFlags(new Inherited()),

            (new IntField('mode', 'mode'))->addFlags(new Inherited()),
            (new IntField('buffer', 'buffer'))->addFlags(new Inherited()),
            (new IntField('offset', 'offset'))->addFlags(new Inherited()),
            (new IntField('min_period', 'minPeriod'))->addFlags(new Inherited()),
            (new IntField('max_period', 'maxPeriod'))->addFlags(new Inherited()),
            (new BoolField('fixed_period', 'fixedPeriod'))->addFlags(new Inherited()),
            new IntField('original_stock', 'originalStock'),
            new StringField('deposit_name', 'depositName'),
            new StringField('deposit_product_number', 'depositProductNumber'),
            (new BoolField('purchasable', 'purchasable'))->addFlags(new Inherited()),
            (new BoolField('active', 'active'))->addFlags(new Inherited()),
            new JsonField('blocked_periods', 'blockedPeriods'),
            new JsonField('rental_times', 'rentalTimes'),
            (new OneToOneAssociationField('product', 'product_id', 'id', ProductDefinition::class, false)),
            (new OneToManyAssociationField(
                'depositPrices',
                RentalProductDepositPriceDefinition::class,
                'rental_product_id'
            ))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('prices', RentalProductPriceDefinition::class, 'rental_product_id'))
            ->addFlags(new CascadeDelete(), new Inherited()),
            (new PriceField('price', 'price'))->addFlags(new Inherited()),
            new PriceField('deposit_price', 'depositPrice'),

            (new CheapestPriceField('cheapest_price', 'cheapestPrice'))->addFlags(new WriteProtected(), new Inherited()),
            new ChildCountField(),

            (new PriceField('bail_price', 'bailPrice'))->addFlags(new Inherited()),
            (new BoolField('bail_active', 'bailActive'))->addFlags(new Inherited()),
            (new FkField('bail_tax_id', 'bailTaxId', TaxDefinition::class))->addFlags(new Inherited()),
            (new ManyToOneAssociationField('bailtax', 'bail_tax_id', TaxDefinition::class, 'id', true))->addFlags(new Inherited()),
        ]);
    }
}
