<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(LexiconSalesChannelEntity $entity)
 * @method void              set(string $key, LexiconSalesChannelEntity $entity)
 * @method LexiconSalesChannelEntity[]    getIterator()
 * @method LexiconSalesChannelEntity[]    getElements()
 * @method LexiconSalesChannelEntity|null get(string $key)
 * @method LexiconSalesChannelEntity|null first()
 * @method LexiconSalesChannelEntity|null last()
 */
class LexiconSalesChannelCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return LexiconSalesChannelEntity::class;
    }
}
