<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ReverseInherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;

use Cbax\ModulLexicon\Core\Content\Bundle\Aggregate\LexiconTranslation\LexiconEntryTranslationDefinition;
use Shopware\Core\Content\Product\ProductDefinition;


class LexiconEntryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cbax_lexicon_entry';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

	public function getEntityClass(): string
    {
        return LexiconEntryEntity::class;
    }

    public function getCollectionClass(): string
    {
        return LexiconEntryCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
			new IntField('impressions', 'impressions'),
			(new DateField('date', 'date'))->addFlags(new Required()),
			new StringField('link', 'link'),
			new StringField('link_target', 'linkTarget'),
			new StringField('listing_type', 'listingType'),
			new IdField('product_stream_id', 'productStreamId'),
			new StringField('product_layout', 'productLayout'),
            new StringField('product_template', 'productTemplate'),
			new StringField('product_slider_width', 'productSliderWidth'),
			new StringField('product_sorting', 'productSorting'),
			new IntField('product_limit', 'productLimit'),
			new StringField('attribute2', 'attribute2'),
			new StringField('attribute3', 'attribute3'),
            (new FkField('media2_id', 'media2Id', MediaDefinition::class))->addFlags(new ApiAware()),
            (new FkField('media3_id', 'media3Id', MediaDefinition::class))->addFlags(new ApiAware()),

			//translatable fields
            (new TranslatedField('title'))->addFlags(new Required()),
            (new TranslatedField('keyword'))->addFlags(new Required()),
            new TranslatedField('description'),
			new TranslatedField('descriptionLong'),
			new TranslatedField('linkDescription'),
			new TranslatedField('metaTitle'),
			new TranslatedField('metaKeywords'),
			new TranslatedField('metaDescription'),
			new TranslatedField('headline'),
			new TranslatedField('attribute1'),
			new TranslatedField('attribute4'),
			new TranslatedField('attribute5'),
			new TranslatedField('attribute6'),

			new TranslationsAssociationField(LexiconEntryTranslationDefinition::class, 'cbax_lexicon_entry_id'),

            (new OneToManyAssociationField('saleschannels', LexiconSalesChannelDefinition::class, 'cbax_lexicon_entry_id'))
                ->addFlags(new CascadeDelete()),
            (new ManyToOneAssociationField('media2', 'media2_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('media3', 'media3_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            /*
            (new OneToManyAssociationField('products', LexiconProductDefinition::class, 'cbax_lexicon_entry_id'))
                ->addFlags(new CascadeDelete()),
            */
            (new ManyToManyAssociationField('products', ProductDefinition::class, LexiconProductDefinition::class, 'cbax_lexicon_entry_id', 'product_id'))->addFlags(new CascadeDelete(), new ReverseInherited('lexiconEntry')),
        ]);
    }
}
