<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Country\CountryDefinition;

class OrderDeliveryStoreDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'acris_order_delivery_store';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return OrderDeliveryStoreCollection::class;
    }

    public function getEntityClass(): string
    {
        return OrderDeliveryStoreEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new VersionField())->addFlags(new ApiAware()),
            (new FkField('store_id', 'storeId', StoreLocatorDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('order_delivery_id', 'orderDeliveryId', OrderDeliveryDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new ReferenceVersionField(OrderDeliveryDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('country_id', 'countryId', CountryDefinition::class))->addFlags(new ApiAware()),
            (new StringField('name', 'name'))->addFlags(new Required(), new ApiAware()),
            (new StringField('department', 'department'))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('country', 'country_id', CountryDefinition::class, 'id', true))->addFlags(new ApiAware()),
            (new StringField('city','city'))->addFlags(new Required()),
            (new StringField('zipcode','zipcode'))->addFlags(new Required(), new ApiAware()),
            (new StringField('street','street'))->addFlags(new Required(), new ApiAware()),
            (new StringField('phone', 'phone'))->addFlags(new ApiAware()),
            (new StringField('email', 'email'))->addFlags(new ApiAware()),
            (new StringField('url', 'url'))->addFlags(new ApiAware()),
            (new LongTextField('opening_hours', 'opening_hours'))->addFlags(new AllowHtml(), new ApiAware()),
            (new StringField('longitude','longitude'))->addFlags(new ApiAware()),
            (new StringField('latitude','latitude'))->addFlags(new ApiAware()),
            (new OneToOneAssociationField('orderDelivery', 'order_delivery_id', 'id' ,OrderDeliveryDefinition::class, false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('store', 'store_id', StoreLocatorDefinition::class, 'id', false))->addFlags(new ApiAware())
        ]);
    }
}
