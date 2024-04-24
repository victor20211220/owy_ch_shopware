<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\Content\Media\MediaEntity;

use Cbax\ModulLexicon\Core\Content\Bundle\Aggregate\LexiconTranslation\LexiconEntryTranslationCollection;

class LexiconEntryEntity extends Entity
{
    use EntityIdTrait;
	
	/**
     * @var int|null
     */
    protected $impressions;
	
	/**
     * @var \DateTimeInterface|null
     */
	protected $date;
	
	/**
     * @var string|null
     */
	protected $link;
	
	/**
     * @var string|null
     */
	protected $linkTarget;
	
	/**
     * @var string|null
     */
	protected $listingType;
	
	/**
     * @var string|null
     */
	protected $productStreamId;
	
	/**
     * @var string|null
     */
	protected $productLayout;

    /**
     * @var string|null
     */
    protected $productTemplate;
	
	/**
     * @var string|null
     */
	protected $productSliderWidth;
	
	/**
     * @var string|null
     */
	protected $productSorting;
	
	/**
     * @var int|null
     */
	protected $productLimit;
	
	/**
     * @var string|null
     */
	protected $attribute2;

    /**
     * @var string|null
     */
    protected $media2Id;
	
	/**
     * @var string|null
     */
	protected $attribute3;

    /**
     * @var string|null
     */
    protected $media3Id;

    /**
     * @var MediaEntity|null
     */
    protected $media2;

    /**
     * @var MediaEntity|null
     */
    protected $media3;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $keyword;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $descriptionLong;

    /**
     * @var string|null
     */
    protected $linkDescription;

    /**
     * @var string|null
     */
    protected $metaTitle;

    /**
     * @var string|null
     */
    protected $metaKeywords;

    /**
     * @var string|null
     */
    protected $metaDescription;

    /**
     * @var string|null
     */
    protected $headline;

    /**
     * @var string|null
     */
    protected $attribute1;

    /**
     * @var string|null
     */
    protected $attribute4;

    /**
     * @var string|null
     */
    protected $attribute5;

    /**
     * @var string|null
     */
    protected $attribute6;

    /**
     * @var SalesChannelCollection|null
     */
    protected $saleschannels;

    /**
     * @var ProductCollection|null
     */
    protected $products;

    /**
     * @var LexiconEntryTranslationCollection|null
     */
    protected $translations;

    /**
     * @return int|null
     */
    public function getImpressions(): ?int
    {
        return $this->impressions;
    }

    /**
     * @param int|null $impressions
     */
    public function setImpressions(?int $impressions): void
    {
        $this->impressions = $impressions;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @param \DateTimeInterface|null $date
     */
    public function setDate(?\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @param string|null $link
     */
    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    /**
     * @return string|null
     */
    public function getLinkTarget(): ?string
    {
        return $this->linkTarget;
    }

    /**
     * @param string|null $linkTarget
     */
    public function setLinkTarget(?string $linkTarget): void
    {
        $this->linkTarget = $linkTarget;
    }

    /**
     * @return string|null
     */
    public function getListingType(): ?string
    {
        return $this->listingType;
    }

    /**
     * @param string|null $listingType
     */
    public function setListingType(?string $listingType): void
    {
        $this->listingType = $listingType;
    }

    /**
     * @return string|null
     */
    public function getProductStreamId(): ?string
    {
        return $this->productStreamId;
    }

    /**
     * @param string|null $productStreamId
     */
    public function setProductStreamId(?string $productStreamId): void
    {
        $this->productStreamId = $productStreamId;
    }

    /**
     * @return string|null
     */
    public function getProductLayout(): ?string
    {
        return $this->productLayout;
    }

    /**
     * @param string|null $productLayout
     */
    public function setProductLayout(?string $productLayout): void
    {
        $this->productLayout = $productLayout;
    }

    /**
     * @return string|null
     */
    public function getProductTemplate(): ?string
    {
        return $this->productTemplate;
    }

    /**
     * @param string|null productTemplate
     */
    public function setProductTemplate(?string $productTemplate): void
    {
        $this->productTemplate = $productTemplate;
    }

    /**
     * @return string|null
     */
    public function getProductSliderWidth(): ?string
    {
        return $this->productSliderWidth;
    }

    /**
     * @param string|null $productSliderWidth
     */
    public function setProductSliderWidth(?string $productSliderWidth): void
    {
        $this->productSliderWidth = $productSliderWidth;
    }

    /**
     * @return string|null
     */
    public function getProductSorting(): ?string
    {
        return $this->productSorting;
    }

    /**
     * @param string|null $productSorting
     */
    public function setProductSorting(?string $productSorting): void
    {
        $this->productSorting = $productSorting;
    }

    /**
     * @return int|null
     */
    public function getProductLimit(): ?int
    {
        return $this->productLimit;
    }

    /**
     * @param int|null $productLimit
     */
    public function setProductLimit(?int $productLimit): void
    {
        $this->productLimit = $productLimit;
    }

    /**
     * @return string|null
     */
    public function getAttribute2(): ?string
    {
        return $this->attribute2;
    }

    /**
     * @param string|null $attribute2
     */
    public function setAttribute2(?string $attribute2): void
    {
        $this->attribute2 = $attribute2;
    }

    /**
     * @return string|null
     */
    public function getAttribute3(): ?string
    {
        return $this->attribute3;
    }

    /**
     * @param string|null $attribute3
     */
    public function setAttribute3(?string $attribute3): void
    {
        $this->attribute3 = $attribute3;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    /**
     * @param string|null $keyword
     */
    public function setKeyword(?string $keyword): void
    {
        $this->keyword = $keyword;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getDescriptionLong(): ?string
    {
        return $this->descriptionLong;
    }

    /**
     * @param string|null $descriptionLong
     */
    public function setDescriptionLong(?string $descriptionLong): void
    {
        $this->descriptionLong = $descriptionLong;
    }

    /**
     * @return string|null
     */
    public function getLinkDescription(): ?string
    {
        return $this->linkDescription;
    }

    /**
     * @param string|null $linkDescription
     */
    public function setLinkDescription(?string $linkDescription): void
    {
        $this->linkDescription = $linkDescription;
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
    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    /**
     * @param string|null $metaKeywords
     */
    public function setMetaKeywords(?string $metaKeywords): void
    {
        $this->metaKeywords = $metaKeywords;
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
     * @return string|null
     */
    public function getHeadline(): ?string
    {
        return $this->headline;
    }

    /**
     * @param string|null $headline
     */
    public function setHeadline(?string $headline): void
    {
        $this->headline = $headline;
    }

    /**
     * @return string|null
     */
    public function getAttribute1(): ?string
    {
        return $this->attribute1;
    }

    /**
     * @param string|null $attribute1
     */
    public function setAttribute1(?string $attribute1): void
    {
        $this->attribute1 = $attribute1;
    }

    /**
     * @return string|null
     */
    public function getAttribute4(): ?string
    {
        return $this->attribute4;
    }

    /**
     * @param string|null $attribute4
     */
    public function setAttribute4(?string $attribute4): void
    {
        $this->attribute4 = $attribute4;
    }

    /**
     * @return string|null
     */
    public function getAttribute5(): ?string
    {
        return $this->attribute5;
    }

    /**
     * @param string|null $attribute5
     */
    public function setAttribute5(?string $attribute5): void
    {
        $this->attribute5 = $attribute5;
    }

    /**
     * @return string|null
     */
    public function getAttribute6(): ?string
    {
        return $this->attribute6;
    }

    /**
     * @param string|null $attribute6
     */
    public function setAttribute6(?string $attribute6): void
    {
        $this->attribute6 = $attribute6;
    }

    /**
     * @return SalesChannelCollection|null
     */
    public function getSaleschannels(): ?SalesChannelCollection
    {
        return $this->saleschannels;
    }

    /**
     * @param SalesChannelCollection|null $saleschannels
     */
    public function setSaleschannels(?SalesChannelCollection $saleschannels): void
    {
        $this->saleschannels = $saleschannels;
    }

    /**
     * @return ProductCollection|null
     */
    public function getProducts(): ?ProductCollection
    {
        return $this->products;
    }

    /**
     * @param ProductCollection|null $products
     */
    public function setProducts(?ProductCollection $products): void
    {
        $this->products = $products;
    }

    /**
     * @return string|null
     */
    public function getMedia2Id(): ?string
    {
        return $this->media2Id;
    }

    /**
     * @param string|null $media2Id
     */
    public function setMedia2Id(?string $media2Id): void
    {
        $this->media2Id = $media2Id;
    }

    /**
     * @return string|null
     */
    public function getMedia3Id(): ?string
    {
        return $this->media3Id;
    }

    /**
     * @param string|null $media3Id
     */
    public function setMedia3Id(?string $media3Id): void
    {
        $this->media3Id = $media3Id;
    }

    /**
     * @return MediaEntity|null
     */
    public function getMedia2(): ?MediaEntity
    {
        return $this->media2;
    }

    /**
     * @param MediaEntity|null $media2
     */
    public function setMedia2(?MediaEntity $media2): void
    {
        $this->media2 = $media2;
    }

    /**
     * @return MediaEntity|null
     */
    public function getMedia3(): ?MediaEntity
    {
        return $this->media3;
    }

    /**
     * @param MediaEntity|null $media3
     */
    public function setMedia3(?MediaEntity $media3): void
    {
        $this->media3 = $media3;
    }

    public function getTranslations(): ?LexiconEntryTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(?LexiconEntryTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

}

