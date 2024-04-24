<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Core\Content\Bundle\Aggregate\LexiconTranslation;

use Cbax\ModulLexicon\Core\Content\Bundle\LexiconEntryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class LexiconEntryTranslationEntity extends TranslationEntity
{
	/**
     * @var string
     */
    protected $title;
	
    /**
     * @var string
     */
    protected $keyword;

    /**
     * @var string
     */
    protected $description;
	
	/**
     * @var string
     */
    protected $descriptionLong;
	
	/**
     * @var string
     */
	protected $linkDescription;
	
	/**
     * @var string
     */
	protected $metaTitle;
	
	/**
     * @var string
     */
	protected $metaKeywords;
	
	/**
     * @var string
     */
	protected $metaDescription;
	
	/**
     * @var string
     */
	protected $headline;
	
	/**
     * @var string
     */
	protected $attribute1;
	
	/**
     * @var string
     */
	protected $attribute4;
	
	/**
     * @var string
     */
	protected $attribute5;
	
	/**
     * @var string
     */
	protected $attribute6;

	public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

	public function getDescriptionLong(): ?string
    {
        return $this->descriptionLong;
    }

    public function setDescriptionLong(string $descriptionLong): void
    {
        $this->descriptionLong = $descriptionLong;
    }
	
	public function getLinkDescription(): ?string
    {
        return $this->linkDescription;
    }

    public function setLinkDescription(string $linkDescription): void
    {
        $this->linkDescription = $linkDescription;
    }
	
	public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(string $metaTitle): void
    {
        $this->metaTitle = $metaTitle;
    }
	
	public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(string $metaKeywords): void
    {
        $this->metaKeywords = $metaKeywords;
    }
	
	public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(string $metaDescription): void
    {
        $this->metaDescription = $metaDescription;
    }
	
	public function getHeadline(): ?string
    {
        return $this->headline;
    }

    public function setHeadline(string $headline): void
    {
        $this->headline = $headline;
    }
	
	public function getAttribute1(): ?string
    {
        return $this->attribute1;
    }

    public function setAttribute1(string $attribute1): void
    {
        $this->attribute1 = $attribute1;
    }
	
	public function getAttribute4(): ?string
    {
        return $this->attribute4;
    }

    public function setAttribute4(string $attribute4): void
    {
        $this->attribute4 = $attribute4;
    }
	
	public function getAttribute5(): ?string
    {
        return $this->attribute5;
    }

    public function setAttribute5(string $attribute5): void
    {
        $this->attribute5 = $attribute5;
    }
	
	public function getAttribute6(): ?string
    {
        return $this->attribute6;
    }

    public function setAttribute6(string $attribute6): void
    {
        $this->attribute6 = $attribute6;
    }

}
