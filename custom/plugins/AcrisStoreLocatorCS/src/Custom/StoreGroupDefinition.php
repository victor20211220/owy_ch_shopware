<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Acris\StoreLocator\Custom\Aggregate\StoreGroupTranslation\StoreGroupTranslationDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class StoreGroupDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'acris_store_group';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return StoreGroupCollection::class;
    }

    public function getEntityClass(): string
    {
        return StoreGroupEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new StringField('internal_id','internalId'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new VersionField())->addFlags(new ApiAware()),
            (new TranslatedField('internalName'))->addFlags(new ApiAware()),
            (new TranslatedField('name'))->addFlags(new ApiAware()),
            (new TranslatedField('seoUrl'))->addFlags(new ApiAware()),
            (new TranslatedField('metaTitle'))->addFlags(new ApiAware()),
            (new TranslatedField('metaDescription'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new BoolField('display', 'display'))->addFlags(new ApiAware()),
            (new BoolField('display_detail', 'displayDetail'))->addFlags(new ApiAware()),
            (new IntField('priority', 'priority'))->addFlags(new ApiAware()),
            (new IntField('group_zoom_factor', 'groupZoomFactor'))->addFlags(new ApiAware()),
            (new BoolField('display_below_map', 'displayBelowMap'))->addFlags(new ApiAware()),
            (new StringField('position', 'position'))->addFlags(new ApiAware()),
            (new JsonField('field_list', 'fieldList'))->addFlags(new ApiAware()),
            (new BoolField('default', 'default'))->addFlags(new ApiAware()),
            (new IntField('icon_width', 'iconWidth'))->addFlags(new ApiAware()),
            (new IntField('icon_height', 'iconHeight'))->addFlags(new ApiAware()),
            (new IntField('icon_anchor_left', 'iconAnchorLeft'))->addFlags(new ApiAware()),
            (new IntField('icon_anchor_right', 'iconAnchorRight'))->addFlags(new ApiAware()),

            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),

            (new FkField('icon_id', 'iconId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('icon', 'icon_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),

            (new OneToManyAssociationField('acrisStores',StoreLocatorDefinition::class,'store_group_id','id'))->addFlags(new CascadeDelete(), new ApiAware()),
            (new TranslationsAssociationField(StoreGroupTranslationDefinition::class, 'acris_store_group_id'))->addFlags(new ApiAware())
        ]);
    }
}
