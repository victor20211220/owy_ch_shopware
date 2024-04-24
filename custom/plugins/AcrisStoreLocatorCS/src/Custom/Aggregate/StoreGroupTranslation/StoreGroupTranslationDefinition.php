<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom\Aggregate\StoreGroupTranslation;

use Acris\StoreLocator\Custom\StoreGroupDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class StoreGroupTranslationDefinition extends EntityTranslationDefinition
{
    public function getEntityName(): string
    {
        return 'acris_store_group_translation';
    }

    public function getCollectionClass(): string
    {
        return StoreGroupTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return StoreGroupTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return StoreGroupDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('internal_name', 'internalName'))->addFlags(new ApiAware()),
            (new StringField('name', 'name'))->addFlags(new ApiAware()),
            (new StringField('seo_url', 'seoUrl'))->addFlags(new ApiAware()),
            (new StringField('meta_title', 'metaTitle'))->addFlags(new ApiAware()),
            (new StringField('meta_description', 'metaDescription'))->addFlags(new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware())
        ]);
    }
}
