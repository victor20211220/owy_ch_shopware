<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Acris\StoreLocator\Custom\Aggregate\StoreLocatorTranslation\StoreLocatorTranslationCollection;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class StoreLocatorEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $internalId;

    /**
     * @var CountryEntity
     */
    protected $country;

    /**
     * @var string
     */
    protected $countryId;

    /**
     * @var CountryStateEntity|null
     */
    protected $state;

    /**
     * @var string|null
     */
    protected $stateId;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $zipcode;

    /**
     * @var string
     */
    protected $street;

    /**
     * @var string|null
     */
    protected $cmsPageId;

    /**
     * @var CmsPageEntity|null
     */
    protected $cmsPage;

    /**
     * @var SalesChannelEntity|null
     */
    protected $salesChannels;

    /**
     * @var RuleCollection|null
     */
    protected $rules;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var string
     */
    protected $longitude;

    /**
     * @var string|null
     */
    protected $storeGroupId;

    /**
     * @var StoreGroupEntity|null
     */
    protected $storeGroup;

    /**
     * @var int|null
     */
    protected $priority;

    /**
     * @var string
     */
    protected $handlerpoints;

    /**
     * @var string
     */
    protected $latitude;

    /**
     * @var OrderDeliveryStoreCollection|null
     */
    protected $acrisOrderDeliveryStore;

    /**
     * @var StoreLocatorTranslationCollection|null
     */
    protected $translations;

    /**
     * @var string|null
     */
    protected $coverId;

    /**
     * @var StoreMediaEntity|null
     */
    protected $cover;

    /**
     * @var StoreMediaCollection|null
     */
    protected $media;

    /**
     * @return SalesChannelEntity|null
     */
    public function getSalesChannels(): ?SalesChannelEntity
    {
        return $this->salesChannels;
    }

    /**
     * @param SalesChannelEntity|null $salesChannels
     */
    public function setSalesChannels(?SalesChannelEntity $salesChannels): void
    {
        $this->salesChannels = $salesChannels;
    }

    /**
     * @return RuleCollection|null
     */
    public function getRules(): ?RuleCollection
    {
        return $this->rules;
    }

    /**
     * @param RuleCollection|null $rules
     */
    public function setRules(?RuleCollection $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * @return string|null
     */
    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    /**
     * @param string|null $internalId
     */
    public function setInternalId(?string $internalId): void
    {
        $this->internalId = $internalId;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getZipcode(): string
    {
        return $this->zipcode;
    }

    /**
     * @param string $zipcode
     */
    public function setZipcode(string $zipcode): void
    {
        $this->zipcode = $zipcode;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }


    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getHandlerpoints(): ?string
    {
        return $this->handlerpoints;
    }

    /**
     * @param string $handlerpoints
     */
    public function setHandlerpoints(string $handlerpoints): void
    {
        $this->handlerpoints = $handlerpoints;
    }

    /**
     * @return string
     */
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     */
    public function setLongitude(string $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return string
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     */
    public function setLatitude(string $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return StoreLocatorTranslationCollection|null
     */
    public function getTranslations(): ?StoreLocatorTranslationCollection
    {
        return $this->translations;
    }

    /**
     * @param StoreLocatorTranslationCollection|null $translations
     */
    public function setTranslations(?StoreLocatorTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    /**
     * @return OrderDeliveryStoreCollection|null
     */
    public function getAcrisOrderDeliveryStore(): ?OrderDeliveryStoreCollection
    {
        return $this->acrisOrderDeliveryStore;
    }

    /**
     * @param OrderDeliveryStoreCollection|null $acrisOrderDeliveryStore
     */
    public function setAcrisOrderDeliveryStore(?OrderDeliveryStoreCollection $acrisOrderDeliveryStore): void
    {
        $this->acrisOrderDeliveryStore = $acrisOrderDeliveryStore;
    }

    /**
     * @return string
     */
    public function getCountryId(): string
    {
        return $this->countryId;
    }

    /**
     * @param string $countryId
     */
    public function setCountryId(string $countryId): void
    {
        $this->countryId = $countryId;
    }

    /**
     * @return CountryEntity
     */
    public function getCountry(): CountryEntity
    {
        return $this->country;
    }

    /**
     * @param CountryEntity $country
     */
    public function setCountry(CountryEntity $country): void
    {
        $this->country = $country;
    }

    /**
     * @return string|null
     */
    public function getStoreGroupId(): ?string
    {
        return $this->storeGroupId;
    }

    /**
     * @param string|null $storeGroupId
     */
    public function setStoreGroupId(?string $storeGroupId): void
    {
        $this->storeGroupId = $storeGroupId;
    }

    /**
     * @return StoreGroupEntity|null
     */
    public function getStoreGroup(): ?StoreGroupEntity
    {
        return $this->storeGroup;
    }

    /**
     * @param StoreGroupEntity|null $storeGroup
     */
    public function setStoreGroup(?StoreGroupEntity $storeGroup): void
    {
        $this->storeGroup = $storeGroup;
    }

    /**
     * @return string|null
     */
    public function getCmsPageId(): ?string
    {
        return $this->cmsPageId;
    }

    /**
     * @param string|null $cmsPageId
     */
    public function setCmsPageId(?string $cmsPageId): void
    {
        $this->cmsPageId = $cmsPageId;
    }

    /**
     * @return CmsPageEntity|null
     */
    public function getCmsPage(): ?CmsPageEntity
    {
        return $this->cmsPage;
    }

    /**
     * @param CmsPageEntity|null $cmsPage
     */
    public function setCmsPage(?CmsPageEntity $cmsPage): void
    {
        $this->cmsPage = $cmsPage;
    }

    /**
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @param int|null $priority
     */
    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return CountryStateEntity|null
     */
    public function getState(): ?CountryStateEntity
    {
        return $this->state;
    }

    /**
     * @param CountryStateEntity|null $state
     */
    public function setState(?CountryStateEntity $state): void
    {
        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getStateId(): ?string
    {
        return $this->stateId;
    }

    /**
     * @param string|null $stateId
     */
    public function setStateId(?string $stateId): void
    {
        $this->stateId = $stateId;
    }

    /**
     * @return string|null
     */
    public function getCoverId(): ?string
    {
        return $this->coverId;
    }

    /**
     * @param string|null $coverId
     */
    public function setCoverId(?string $coverId): void
    {
        $this->coverId = $coverId;
    }

    /**
     * @return StoreMediaEntity|null
     */
    public function getCover(): ?StoreMediaEntity
    {
        return $this->cover;
    }

    /**
     * @param StoreMediaEntity|null $cover
     */
    public function setCover(?StoreMediaEntity $cover): void
    {
        $this->cover = $cover;
    }

    /**
     * @return StoreMediaCollection|null
     */
    public function getMedia(): ?StoreMediaCollection
    {
        return $this->media;
    }

    /**
     * @param StoreMediaCollection|null $media
     */
    public function setMedia(?StoreMediaCollection $media): void
    {
        $this->media = $media;
    }
}
