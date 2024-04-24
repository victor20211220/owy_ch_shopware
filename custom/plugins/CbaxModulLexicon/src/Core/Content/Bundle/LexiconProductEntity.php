<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class LexiconProductEntity extends Entity
{
    use EntityIdTrait;
	
	/**
     * @var string
     */
    protected $cbaxLexiconEntryId;
	
    /**
     * @var string
     */
    protected $productId;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var ProductEntity|null
     */
    protected $product;

    /**
     * @var LexiconEntryEntity|null
     */
    protected $lexiconEntry;

	public function getCbaxLexiconEntryId(): ?string
    {
        return $this->cbaxLexiconEntryId;
    }

    public function setCbaxLexiconEntryId(string $cbaxLexiconEntryId): void
    {
        $this->cbaxLexiconEntryId = $cbaxLexiconEntryId;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return ProductEntity|null
     */
    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    /**
     * @param ProductEntity|null $product
     */
    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    /**
     * @return LexiconEntryEntity|null
     */
    public function getLexiconEntry(): ?LexiconEntryEntity
    {
        return $this->lexiconEntry;
    }

    /**
     * @param LexiconEntryEntity|null $lexiconEntry
     */
    public function setLexiconEntry(?LexiconEntryEntity $lexiconEntry): void
    {
        $this->lexiconEntry = $lexiconEntry;
    }

}

