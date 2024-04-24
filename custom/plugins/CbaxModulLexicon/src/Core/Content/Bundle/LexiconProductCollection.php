<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(LexiconProductEntity $entity)
 * @method void              set(string $key, LexiconProductEntity $entity)
 * @method LexiconProductEntity[]    getIterator()
 * @method LexiconProductEntity[]    getElements()
 * @method LexiconProductEntity|null get(string $key)
 * @method LexiconProductEntity|null first()
 * @method LexiconProductEntity|null last()
 */
class LexiconProductCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return LexiconProductEntity::class;
    }
}
