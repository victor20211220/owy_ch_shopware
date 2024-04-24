<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Content\Product\ProductDefinition as ShopwareProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
//use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
//use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;


class LexiconProductDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'cbax_lexicon_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('cbax_lexicon_entry_id', 'cbaxLexiconEntryId', LexiconEntryDefinition::class))->addFlags(new Required(), new PrimaryKey()),

			(new FkField('product_id', 'productId', ShopwareProductDefinition::class))->addFlags(new Required(), new PrimaryKey()),
            (new ReferenceVersionField(ShopwareProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),

            new IntField('position', 'position'),

			new ManyToOneAssociationField('product', 'product_id', ShopwareProductDefinition::class, 'id', false),
            new ManyToOneAssociationField('lexiconEntry', 'cbax_lexicon_entry_id', LexiconEntryDefinition::class, 'id', false),
        ]);
    }
}
