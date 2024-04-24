<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreLocator;

use Acris\StoreLocator\Custom\StoreGroupCollection;
use Shopware\Storefront\Page\Page;

class StoreLocatorPage extends Page
{

    private ?array $mediaStore;
    private ?array $mediaHome;
    private ?StoreGroupCollection $groups;

    public function __construct(?array $mediaStore, ?array $mediaHome, ?StoreGroupCollection $groups)
    {
        $this->mediaStore = $mediaStore;
        $this->mediaHome = $mediaHome;
        $this->groups = $groups;
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
     * @return StoreGroupCollection|null
     */
    public function getGroups(): ?StoreGroupCollection
    {
        return $this->groups;
    }

    /**
     * @param StoreGroupCollection|null $groups
     */
    public function setGroups(?StoreGroupCollection $groups): void
    {
        $this->groups = $groups;
    }
}
