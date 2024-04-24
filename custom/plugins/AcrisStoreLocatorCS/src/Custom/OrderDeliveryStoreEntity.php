<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Country\CountryEntity;

class OrderDeliveryStoreEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $storeId;

    /**
     * @var string
     */
    protected $orderDeliveryId;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $department;

    /**
     * @var string|null
     */
    protected $phone;

    /**
     * @var string|null
     */
    protected $email;

    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $opening_hours;

    /**
     * @var CountryEntity
     */
    protected $country;

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
     * @var string
     */
    protected $longitude;

    /**
     * @var string
     */
    protected $latitude;

    /**
     * @var StoreLocatorEntity|null
     */
    protected $store;

    /**
     * @var OrderDeliveryEntity|null
     */
    protected $orderDelivery;

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
    public function getOrderDeliveryId(): string
    {
        return $this->orderDeliveryId;
    }

    /**
     * @param string $orderDeliveryId
     */
    public function setOrderDeliveryId(string $orderDeliveryId): void
    {
        $this->orderDeliveryId = $orderDeliveryId;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getDepartment(): ?string
    {
        return $this->department;
    }

    /**
     * @param string|null $department
     */
    public function setDepartment(?string $department): void
    {
        $this->department = $department;
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
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getOpeningHours(): ?string
    {
        return $this->opening_hours;
    }

    /**
     * @param string|null $opening_hours
     */
    public function setOpeningsHours(?string $opening_hours): void
    {
        $this->opening_hours = $opening_hours;
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
     * @return OrderDeliveryEntity|null
     */
    public function getOrderDelivery(): ?OrderDeliveryEntity
    {
        return $this->orderDelivery;
    }

    /**
     * @param OrderDeliveryEntity|null $orderDelivery
     */
    public function setOrderDelivery(?OrderDeliveryEntity $orderDelivery): void
    {
        $this->orderDelivery = $orderDelivery;
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
}
