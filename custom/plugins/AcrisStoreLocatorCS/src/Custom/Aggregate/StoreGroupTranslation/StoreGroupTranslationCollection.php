<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom\Aggregate\StoreGroupTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(StoreGroupTranslationEntity $entity)
 * @method void                         set(string $key, StoreGroupTranslationEntity $entity)
 * @method StoreGroupTranslationEntity[]    getIterator()
 * @method StoreGroupTranslationEntity[]    getElements()
 * @method StoreGroupTranslationEntity|null get(string $key)
 * @method StoreGroupTranslationEntity|null first()
 * @method StoreGroupTranslationEntity|null last()
 */
class StoreGroupTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return StoreGroupTranslationEntity::class;
    }
}
