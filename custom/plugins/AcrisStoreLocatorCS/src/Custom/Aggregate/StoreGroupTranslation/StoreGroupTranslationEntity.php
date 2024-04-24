<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom\Aggregate\StoreGroupTranslation;

use Acris\StoreLocator\Custom\StoreGroupEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Language\LanguageEntity;

class StoreGroupTranslationEntity extends TranslationEntity
{

    /**
     * @var string
     */
    protected $acrisStoreGroupId;
    /**
     * @var string
     */
    protected $internalName;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var StoreGroupEntity
     */
    protected $acrisStoreGroup;

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
    protected $customFields;

    /**
     * @return Struct[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @param Struct[] $extensions
     */
    public function setExtensions(array $extensions): void
    {
        $this->extensions = $extensions;
    }

    /**
     * @return string
     */
    public function getAcrisStoreGroupId(): string
    {
        return $this->acrisStoreGroupId;
    }

    /**
     * @param string $acrisStoreGroupId
     */
    public function setAcrisStoreGroupId(string $acrisStoreGroupId): void
    {
        $this->acrisStoreGroupId = $acrisStoreGroupId;
    }

    /**
     * @return string
     */
    public function getInternalName(): string
    {
        return $this->internalName;
    }

    /**
     * @param string $internalName
     */
    public function setInternalName(string $internalName): void
    {
        $this->internalName = $internalName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array|null
     */
    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    /**
     * @param array|null $customFields
     */
    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }

    /**
     * @return StoreGroupEntity
     */
    public function getAcrisStoreGroup(): StoreGroupEntity
    {
        return $this->acrisStoreGroup;
    }

    /**
     * @param StoreGroupEntity $acrisStoreGroup
     */
    public function setAcrisStoreGroup(StoreGroupEntity $acrisStoreGroup): void
    {
        $this->acrisStoreGroup = $acrisStoreGroup;
    }

    /**
     * @return string
     */
    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    /**
     * @param string $languageId
     */
    public function setLanguageId(string $languageId): void
    {
        $this->languageId = $languageId;
    }

    /**
     * @return LanguageEntity|null
     */
    public function getLanguage(): ?LanguageEntity
    {
        return $this->language;
    }

    /**
     * @param LanguageEntity|null $language
     */
    public function setLanguage(?LanguageEntity $language): void
    {
        $this->language = $language;
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
}
