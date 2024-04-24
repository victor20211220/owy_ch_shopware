<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom\Aggregate\StoreLocatorTranslation;

use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class StoreLocatorTranslationEntity extends TranslationEntity
{

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
     * @var StoreLocatorEntity
     */
    protected $storeLocator;

    /**
     * @var string|null
     */
    protected $seoUrl;

    /**
     * @var string|null
     */
    protected $metaTitle;

    /**
     * @var string|null
     */
    protected $metaDescription;

    /**
     * @var array|null
     */
    protected $slotConfig;

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
     * @return StoreLocatorEntity
     */
    public function getStoreLocator(): StoreLocatorEntity
    {
        return $this->storeLocator;
    }

    /**
     * @param StoreLocatorEntity $storeLocator
     */
    public function setStoreLocator(StoreLocatorEntity $storeLocator): void
    {
        $this->storeLocator = $storeLocator;
    }

    /**
     * @return string|null
     */
    public function getSeoUrl(): ?string
    {
        return $this->seoUrl;
    }

    /**
     * @param string|null $seoUrl
     */
    public function setSeoUrl(?string $seoUrl): void
    {
        $this->seoUrl = $seoUrl;
    }

    /**
     * @return string|null
     */
    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    /**
     * @param string|null $metaTitle
     */
    public function setMetaTitle(?string $metaTitle): void
    {
        $this->metaTitle = $metaTitle;
    }

    /**
     * @return string|null
     */
    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    /**
     * @param string|null $metaDescription
     */
    public function setMetaDescription(?string $metaDescription): void
    {
        $this->metaDescription = $metaDescription;
    }

    /**
     * @return array|null
     */
    public function getSlotConfig(): ?array
    {
        return $this->slotConfig;
    }

    /**
     * @param array|null $slotConfig
     */
    public function setSlotConfig(?array $slotConfig): void
    {
        $this->slotConfig = $slotConfig;
    }
}
