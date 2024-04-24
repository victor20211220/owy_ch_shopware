<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom\Aggregate\StoreLocatorTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(StoreLocatorTranslationEntity $entity)
 * @method void                         set(string $key, StoreLocatorTranslationEntity $entity)
 * @method StoreLocatorTranslationEntity[]    getIterator()
 * @method StoreLocatorTranslationEntity[]    getElements()
 * @method StoreLocatorTranslationEntity|null get(string $key)
 * @method StoreLocatorTranslationEntity|null first()
 * @method StoreLocatorTranslationEntity|null last()
 */
class StoreLocatorTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return StoreLocatorTranslationEntity::class;
    }
}
