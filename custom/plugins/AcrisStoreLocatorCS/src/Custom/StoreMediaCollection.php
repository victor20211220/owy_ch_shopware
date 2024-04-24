<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(StoreMediaEntity $entity)
 * @method void              set(string $key, StoreMediaEntity $entity)
 * @method StoreMediaEntity[]    getIterator()
 * @method StoreMediaEntity[]    getElements()
 * @method StoreMediaEntity|null get(string $key)
 * @method StoreMediaEntity|null first()
 * @method StoreMediaEntity|null last()
 */
class StoreMediaCollection extends EntityCollection
{
    public function getStoreIds(): array
    {
        return $this->fmap(function (StoreMediaEntity $storeMedia) {
            return $storeMedia->getStoreId();
        });
    }

    public function filterByStoreId(string $id): self
    {
        return $this->filter(function (StoreMediaEntity $storeMedia) use ($id) {
            return $storeMedia->getStoreId() === $id;
        });
    }

    public function getMediaIds(): array
    {
        return $this->fmap(function (StoreMediaEntity $storeMedia) {
            return $storeMedia->getMediaId();
        });
    }

    public function filterByMediaId(string $id): self
    {
        return $this->filter(function (StoreMediaEntity $storeMedia) use ($id) {
            return $storeMedia->getMediaId() === $id;
        });
    }

    public function getMedia(): MediaCollection
    {
        return new MediaCollection(
            $this->fmap(function (StoreMediaEntity $storeMedia) {
                return $storeMedia->getMedia();
            })
        );
    }

    public function getApiAlias(): string
    {
        return 'store_media_collection';
    }

    protected function getExpectedClass(): string
    {
        return StoreMediaEntity::class;
    }
}
