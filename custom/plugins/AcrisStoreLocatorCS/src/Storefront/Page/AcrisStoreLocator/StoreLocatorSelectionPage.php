<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreLocator;

use Acris\StoreLocator\Custom\StoreLocatorCollection;
use Shopware\Storefront\Page\Page;

class StoreLocatorSelectionPage extends Page
{
    private ?StoreLocatorCollection $stores;

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
}
