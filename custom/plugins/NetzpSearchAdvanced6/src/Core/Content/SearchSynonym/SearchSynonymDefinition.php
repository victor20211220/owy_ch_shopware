<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Core\Content\SearchSynonym;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class SearchSynonymDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 's_plugin_netzp_search_synonyms';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return SearchSynonymEntity::class;
    }

    public function getCollectionClass(): string
    {
        return SearchSynonymCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),

            new StringField('synonym', 'synonym'),
            new StringField('replace', 'replace')
        ]);
    }
}
