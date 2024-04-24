<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom\Aggregate\StoreLocatorTranslation;

use Acris\StoreLocator\Custom\StoreLocatorDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class StoreLocatorTranslationDefinition extends EntityTranslationDefinition
{
    public function getEntityName(): string
    {
        return 'acris_store_locator_translation';
    }

    public function getCollectionClass(): string
    {
        return StoreLocatorTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return StoreLocatorTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return StoreLocatorDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new StringField('department', 'department')),
            (new StringField('phone', 'phone')),
            (new StringField('email', 'email')),
            (new StringField('url', 'url')),
            (new LongTextField('opening_hours', 'opening_hours'))->addFlags(new AllowHtml()),
            (new StringField('seo_url', 'seoUrl')),
            (new StringField('meta_title', 'metaTitle')),
            (new StringField('meta_description', 'metaDescription')),
            new JsonField('slot_config', 'slotConfig')
        ]);
    }
}
