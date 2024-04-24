<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class LexiconSalesChannelDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cbax_lexicon_sales_channel';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

	public function getEntityClass(): string
    {
        return LexiconSalesChannelEntity::class;
    }

    public function getCollectionClass(): string
    {
        return LexiconSalesChannelCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('cbax_lexicon_entry_id', 'cbaxLexiconEntryId', LexiconEntryDefinition::class))->addFlags(new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            new ManyToOneAssociationField('lexiconEntry', 'cbax_lexicon_entry_id', LexiconEntryDefinition::class, 'id', false),
        ]);
    }
}
