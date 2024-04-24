<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreGroup;

use Acris\StoreLocator\Custom\StoreGroupEntity;
use Acris\StoreLocator\Custom\StoreLocatorCollection;
use Petstore30\controllers\Store;
use Shopware\Storefront\Page\Page;

class StoreGroupPage extends Page
{

    private ?array $mediaStore;
    private ?array $mediaHome;
    private ?string $groupId;
    private ?StoreGroupEntity $group;
    private ?StoreLocatorCollection $stores;
    private ?bool $coverCover;

    public function __construct(?array $mediaStore, ?array $mediaHome, ?string $groupId, ?StoreLocatorCollection $stores, ?StoreGroupEntity $group, ?bool $hasCover = false)
    {
        $this->mediaStore = $mediaStore;
        $this->mediaHome = $mediaHome;
        $this->groupId = $groupId;
        $this->stores = $stores;
        $this->group = $group;
        $this->hasCover = $hasCover;
    }

    /**
     * @return array|null
     */
    public function getMediaStore(): ?array
    {
        return $this->mediaStore;
    }

    /**
     * @param $mediaStore
     */
    public function setMediaStore($mediaStore): void
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
     * @param $mediaHome
     */
    public function setMediaHome($mediaHome): void
    {
        $this->mediaHome = $mediaHome;
    }

    /**
     * @return string|null
     */
    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    /**
     * @param string|null $groupId
     */
    public function setGroupId(?string $groupId): void
    {
        $this->groupId = $groupId;
    }

    /**
     * @return StoreLocatorCollection|null
     */
    public function getStores(): ?StoreLocatorCollection
    {
        return $this->stores;
    }

    /**
     * @param StoreLocatorCollection|null $stores
     */
    public function setStores(?StoreLocatorCollection $stores): void
    {
        $this->stores = $stores;
    }

    /**
     * @return StoreGroupEntity|null
     */
    public function getGroup(): ?StoreGroupEntity
    {
        return $this->group;
    }

    /**
     * @param StoreGroupEntity|null $group
     */
    public function setGroup(?StoreGroupEntity $group): void
    {
        $this->group = $group;
    }

    /**
     * @return bool|null
     */
    public function getHasCover(): ?bool
    {
        return $this->hasCover;
    }

    /**
     * @param bool|null $hasCover
     */
    public function setHasCover(?bool $hasCover): void
    {
        $this->hasCover = $hasCover;
    }
}
