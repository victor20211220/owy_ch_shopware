<?php declare(strict_types=1);

namespace Acris\StoreLocator\Storefront\Page\AcrisStoreLocatorDetail;

use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Shopware\Storefront\Page\Page;

class StoreLocatorDetailPage extends Page
{
    private StoreLocatorEntity $store;

    public function __construct(?StoreLocatorEntity $store)
    {
        $this->store = $store;
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
