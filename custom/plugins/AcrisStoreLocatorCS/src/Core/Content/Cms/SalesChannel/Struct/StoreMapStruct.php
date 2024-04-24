<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\Cms\SalesChannel\Struct;

use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Shopware\Core\Framework\Struct\Struct;

class StoreMapStruct extends Struct
{

    private ?array $mediaStore;
    private ?array $mediaHome;
    private ?StoreLocatorEntity $store;

    public function __construct(?array $mediaStore, ?array $mediaHome, ?StoreLocatorEntity $store)
    {
        $this->mediaStore = $mediaStore;
        $this->mediaHome = $mediaHome;
        $this->store = $store;
    }

    /**
     * @return array|null
     */
    public function getMediaStore(): ?array
    {
        return $this->mediaStore;
    }

    /**
     * @param array|null $mediaStore
     */
    public function setMediaStore(?array $mediaStore): void
    {
        $this->mediaStore = $mediaStore;
    }

    /**
     * @return array|null
     */
    public function getMediaHome(): ?array
    {
        return $this->mediaHome;
    }

    /**
     * @param array|null $mediaHome
     */
    public function setMediaHome(?array $mediaHome): void
    {
        $this->mediaHome = $mediaHome;
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
}
