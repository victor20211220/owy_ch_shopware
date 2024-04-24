<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class StoreMediaEntity extends Entity
{
    use EntityIdTrait;
    use EntityCustomFieldsTrait;

    /**
     * @var string
     */
    protected $storeId;

    /**
     * @var string
     */
    protected $mediaId;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var MediaEntity|null
     */
    protected $media;

    /**
     * @var StoreLocatorEntity|null
     */
    protected $store;

    /**
     * @var StoreLocatorCollection|null
     */
    protected $coverStores;

    /**
     * @return string
     */
    public function getStoreId(): string
    {
        return $this->storeId;
    }

    /**
     * @param string $storeId
     */
    public function setStoreId(string $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * @return string
     */
    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    /**
     * @param string $mediaId
     */
    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return MediaEntity|null
     */
    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    /**
     * @param MediaEntity|null $media
     */
    public function setMedia(?MediaEntity $media): void
    {
        $this->media = $media;
    }

    /**
     * @return StoreLocatorEntity|null
     */
    public function getStore(): ?StoreLocatorEntity
    {
        return $this->store;
    }

    /**
     * @param StoreLocatorEntity|null $store
     */
    public function setStore(?StoreLocatorEntity $store): void
    {
        $this->store = $store;
    }

    /**
     * @return StoreLocatorCollection|null
     */
    public function getCoverStores(): ?StoreLocatorCollection
    {
        return $this->coverStores;
    }

    /**
     * @param StoreLocatorCollection|null $coverStores
     */
    public function setCoverStores(?StoreLocatorCollection $coverStores): void
    {
        $this->coverStores = $coverStores;
    }
}
