<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              		add(LexiconEntryEntity $entity)
 * @method void              		set(string $key, LexiconEntryEntity $entity)
 * @method LexiconEntryEntity[]		getIterator()
 * @method LexiconEntryEntity[]		getElements()
 * @method LexiconEntryEntity|null	get(string $key)
 * @method LexiconEntryEntity|null	first()
 * @method LexiconEntryEntity|null	last()
 */
class LexiconEntryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return LexiconEntryEntity::class;
    }
}
