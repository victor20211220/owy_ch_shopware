<?php

namespace OwyPhotoExchange\Storefront\Page;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;


class PostEntityDefinition extends EntityDefinition
{

    public const ENTITY_NAME = "photo_exchange_post";

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('customer', 'customerId', CustomerDefinition::class)),
            (new FkField('category', 'categoryId', CategoryEntityDefinition::class)),
            (new StringField('headline', 'headline')),
            (new StringField('body', 'body', 65535)), // 65535 is the max length (characters) of TEXT field in MySQL
            (new JsonField('images', 'images')),
            (new StringField('status', 'status')),
            (new BoolField('is_active', 'isActive')),
//
            new ManyToOneAssociationField('category', 'category', CategoryEntityDefinition::class),
            new ManyToOneAssociationField('customer', 'customer', CustomerDefinition::class),
        ]);
    }

}