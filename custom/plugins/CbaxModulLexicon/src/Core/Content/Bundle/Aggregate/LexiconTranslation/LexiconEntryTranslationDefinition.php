<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle\Aggregate\LexiconTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;

use Cbax\ModulLexicon\Core\Content\Bundle\LexiconEntryDefinition;

class LexiconEntryTranslationDefinition extends EntityTranslationDefinition
{
    public function getEntityName(): string
    {
        return 'cbax_lexicon_entry_translation';
    }

    public function getCollectionClass(): string
    {
        return LexiconEntryTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return LexiconEntryTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return LexiconEntryDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
		return new FieldCollection([
			(new StringField('title', 'title'))->addFlags(new Required()),
            (new StringField('keyword', 'keyword'))->addFlags(new Required()),
            (new LongTextField('description', 'description'))->addFlags(new AllowHtml()),
			(new LongTextField('description_long', 'descriptionLong'))->addFlags(new AllowHtml()),
			new StringField('link_description', 'linkDescription'),
			new StringField('meta_title', 'metaTitle'),
			new StringField('meta_keywords', 'metaKeywords'),
			new StringField('meta_description', 'metaDescription'),
			new StringField('headline', 'headline'),
			(new LongTextField('attribute1', 'attribute1'))->addFlags(new AllowHtml()),
			new StringField('attribute4', 'attribute4'),
			new StringField('attribute5', 'attribute5'),
			new StringField('attribute6', 'attribute6')
        ]);
    }
}
