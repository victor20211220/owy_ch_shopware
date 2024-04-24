<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Acris\StoreLocator\Custom\Aggregate\StoreGroupTranslation\StoreGroupTranslationCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Struct\Struct;

class StoreGroupEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $internalId;

    /**
     * @var int|null
     */
    protected $priority;

    /**
     * @var int|null
     */
    protected $iconWidth;

    /**
     * @var int|null
     */
    protected $iconHeight;

    /**
     * @var int|null
     */
    protected $iconAnchorLeft;

    /**
     * @var int|null
     */
    protected $iconAnchorRight;

    /**
     * @var int|null
     */
    protected $groupZoomFactor;

    /**
     * @var boolean|null
     */
    protected $active;

    /**
     * @var boolean|null
     */
    protected $display;

    /**
     * @var boolean|null
     */
    protected $default;

    /**
     * @var boolean|null
     */
    protected $displayBelowMap;

    /**
     * @var boolean|null
     */
    protected $displayDetail;

    /**
     * @var string|null
     */
    protected $mediaId;

    /**
     * @var MediaEntity|null
     */
    protected $media;

    /**
     * @var string|null
     */
    protected $iconId;

    /**
     * @var MediaEntity|null
     */
    protected $icon;

    /**
     * @var string|null
     */
    protected $position;

    /**
     * @var array|null
     */
    protected $fieldList;

    /**
     * @var StoreLocatorCollection|null
     */
    protected $acrisStores;

    /**
     * @var StoreGroupTranslationCollection|null
     */
    protected $translations;

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
     * @return bool|null
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool|null $active
     */
    public function setActive(?bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return bool|null
     */
    public function getDisplay(): ?bool
    {
        return $this->display;
    }

    /**
     * @param bool|null $display
     */
    public function setDisplay(?bool $display): void
    {
        $this->display = $display;
    }

    /**
     * @return bool|null
     */
    public function getDisplayBelowMap(): ?bool
    {
        return $this->displayBelowMap;
    }

    /**
     * @param bool|null $displayBelowMap
     */
    public function setDisplayBelowMap(?bool $displayBelowMap): void
    {
        $this->displayBelowMap = $displayBelowMap;
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
     * @return StoreLocatorCollection|null
     */
    public function getAcrisStores(): ?StoreLocatorCollection
    {
        return $this->acrisStores;
    }

    /**
     * @param StoreLocatorCollection|null $acrisStores
     */
    public function setAcrisStores(?StoreLocatorCollection $acrisStores): void
    {
        $this->acrisStores = $acrisStores;
    }

    /**
     * @return StoreGroupTranslationCollection|null
     */
    public function getTranslations(): ?StoreGroupTranslationCollection
    {
        return $this->translations;
    }

    /**
     * @param StoreGroupTranslationCollection|null $translations
     */
    public function setTranslations(?StoreGroupTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    /**
     * @return string|null
     */
    public function getPosition(): ?string
    {
        return $this->position;
    }

    /**
     * @param string|null $position
     */
    public function setPosition(?string $position): void
    {
        $this->position = $position;
    }

    /**
     * @return array|null
     */
    public function getFieldList(): ?array
    {
        return $this->fieldList;
    }

    /**
     * @param array|null $fieldList
     */
    public function setFieldList(?array $fieldList): void
    {
        $this->fieldList = $fieldList;
    }

    /**
     * @return bool|null
     */
    public function getDefault(): ?bool
    {
        return $this->default;
    }

    /**
     * @param bool|null $default
     */
    public function setDefault(?bool $default): void
    {
        $this->default = $default;
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
     * @return int|null
     */
    public function getGroupZoomFactor(): ?int
    {
        return $this->groupZoomFactor;
    }

    /**
     * @param int|null $groupZoomFactor
     */
    public function setGroupZoomFactor(?int $groupZoomFactor): void
    {
        $this->groupZoomFactor = $groupZoomFactor;
    }

    /**
     * @return string|null
     */
    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    /**
     * @param string|null $mediaId
     */
    public function setMediaId(?string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    /**
     * @return bool|null
     */
    public function getDisplayDetail(): ?bool
    {
        return $this->displayDetail;
    }

    /**
     * @param bool|null $displayDetail
     */
    public function setDisplayDetail(?bool $displayDetail): void
    {
        $this->displayDetail = $displayDetail;
    }

    /**
     * @return string|null
     */
    public function getIconId(): ?string
    {
        return $this->iconId;
    }

    /**
     * @param string|null $iconId
     */
    public function setIconId(?string $iconId): void
    {
        $this->iconId = $iconId;
    }

    /**
     * @return MediaEntity|null
     */
    public function getIcon(): ?MediaEntity
    {
        return $this->icon;
    }

    /**
     * @param MediaEntity|null $icon
     */
    public function setIcon(?MediaEntity $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * @return int|null
     */
    public function getIconWidth(): ?int
    {
        return $this->iconWidth;
    }

    /**
     * @param int|null $iconWidth
     */
    public function setIconWidth(?int $iconWidth): void
    {
        $this->iconWidth = $iconWidth;
    }

    /**
     * @return int|null
     */
    public function getIconHeight(): ?int
    {
        return $this->iconHeight;
    }

    /**
     * @param int|null $iconHeight
     */
    public function setIconHeight(?int $iconHeight): void
    {
        $this->iconHeight = $iconHeight;
    }

    /**
     * @return int|null
     */
    public function getIconAnchorLeft(): ?int
    {
        return $this->iconAnchorLeft;
    }

    /**
     * @param int|null $iconAnchorLeft
     */
    public function setIconAnchorLeft(?int $iconAnchorLeft): void
    {
        $this->iconAnchorLeft = $iconAnchorLeft;
    }

    /**
     * @return int|null
     */
    public function getIconAnchorRight(): ?int
    {
        return $this->iconAnchorRight;
    }

    /**
     * @param int|null $iconAnchorRight
     */
    public function setIconAnchorRight(?int $iconAnchorRight): void
    {
        $this->iconAnchorRight = $iconAnchorRight;
    }
}
