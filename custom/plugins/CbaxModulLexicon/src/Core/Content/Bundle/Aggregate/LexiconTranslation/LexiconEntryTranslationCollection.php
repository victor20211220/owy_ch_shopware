<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Core\Content\Bundle\Aggregate\LexiconTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void									add(LexiconEntryTranslationEntity $entity)
 * @method void									set(string $key, LexiconEntryTranslationEntity $entity)
 * @method LexiconEntryTranslationEntity[]		getIterator()
 * @method LexiconEntryTranslationEntity[]		getElements()
 * @method LexiconEntryTranslationEntity|null	get(string $key)
 * @method LexiconEntryTranslationEntity|null	first()
 * @method LexiconEntryTranslationEntity|null	last()
 */
class LexiconEntryTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return LexiconEntryTranslationEntity::class;
    }

    public function getLanguageIds(): array
    {
        return $this->fmap(fn (LexiconEntryTranslationEntity $lexiconEntryTranslation) => $lexiconEntryTranslation->getLanguageId());
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(fn (LexiconEntryTranslationEntity $lexiconEntryTranslation) => $lexiconEntryTranslation->getLanguageId() === $id);
    }
}
