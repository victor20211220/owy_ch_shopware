<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Entities\RentalProduct;

use Rhiem\RhiemRentalProducts\Entities\RentalProductDepositPrice\RentalProductDepositPriceCollection;
use Rhiem\RhiemRentalProducts\Entities\RentalProductPrice\RentalProductPriceCollection;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceContainer;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\System\Tax\TaxEntity;

class RentalProductEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $productId;

    /**
     * @var ProductEntity
     */
    protected $product;

    /**
     * @var int
     */
    protected $mode;

    /**
     * @var int|null;
     */
    protected $buffer;

    /**
     * @var int|null
     */
    protected $offset;

    /**
     * @var int|null
     */
    protected $minPeriod;

    /**
     * @var int|null
     */
    protected $maxPeriod;

    /**
     * @var bool|null
     */
    protected $fixedPeriod;

    /**
     * @var string|null
     */
    protected $depositName;

    /**
     * @var string|null
     */
    protected $depositProductNumber;

    /**
     * @var bool|null
     */
    protected $purchasable;

    /**
     * @var bool|null
     */
    protected $active;

    /**
     * @var array|null
     */
    protected $blockedPeriods;

    /**
     * @var array|null
     */
    protected $rentalTimes;

    /**
     * @var PriceCollection|null
     */
    protected $price;

    /**
     * @var PriceCollection|null
     */
    protected $depositPrice;

    /**
     * The container will be resolved on product.loaded event and
     * the detected cheapest price will be set for the current context rules
     *
     * @var CheapestPrice|CheapestPriceContainer|null
     */
    protected $cheapestPrice;

    /**
     * @var CheapestPriceContainer|null
     */
    protected $cheapestPriceContainer;

    /**
     * @var RentalProductPriceCollection
     */
    protected $prices;

    /**
     * @var RentalProductDepositPriceCollection
     */
    protected $depositPrices;

    /**
     * @var int|null
     */
    protected $originalStock;

    /**
     * @var array|null
     */
    protected $bail;

    /**
     * @var string|null
     */
    protected $parentId;

    /**
     * @var RentalProductEntity|null
     */
    protected $parent;

    /**
     * @var RentalProductCollection|null
     */
    protected $children;

    /**
     * @var bool|null
     */
    protected $bailActive;

    /**
     * @var PriceCollection|null
     */
    protected $bailPrice;

    /**
     * @var string|null
     */
    protected $bailTaxId;

    /**
     * @var TaxEntity|null
     */
    protected $bailtax;

    /**
     * @var TaxEntity|null
     */
    protected $tax;

    /**
     * @var string|null
     */
    protected $taxId;

    /**
     * @var int|null
     */
    protected $childCount;

    public function __construct()
    {
        $this->prices = new RentalProductPriceCollection();
        $this->depositPrices = new RentalProductDepositPriceCollection();
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    public function getBuffer(): ?int
    {
        return $this->buffer;
    }

    public function setBuffer(?int $buffer): void
    {
        $this->buffer = $buffer;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(?int $offset): void
    {
        $this->offset = $offset;
    }

    public function getMinPeriod(): ?int
    {
        return $this->minPeriod;
    }

    public function setMinPeriod(?int $minPeriod): void
    {
        $this->minPeriod = $minPeriod;
    }

    public function getMaxPeriod(): ?int
    {
        return $this->maxPeriod;
    }

    public function setMaxPeriod(?int $maxPeriod): void
    {
        $this->maxPeriod = $maxPeriod;
    }

    public function getFixedPeriod(): ?bool
    {
        return $this->fixedPeriod;
    }

    public function setFixedPeriod(?bool $fixedPeriod): void
    {
        $this->fixedPeriod = $fixedPeriod;
    }

    public function getDepositName(): ?string
    {
        return $this->depositName;
    }

    public function setDepositName(?string $depositName): void
    {
        $this->depositName = $depositName;
    }

    public function getDepositProductNumber(): ?string
    {
        return $this->depositProductNumber;
    }

    public function setDepositProductNumber(?string $depositProductNumber): void
    {
        $this->depositProductNumber = $depositProductNumber;
    }

    public function isPurchasable(): ?bool
    {
        return $this->purchasable;
    }

    public function setPurchasable(bool $purchasable): void
    {
        $this->purchasable = $purchasable;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getBlockedPeriods(): ?array
    {
        return $this->blockedPeriods;
    }

    public function setBlockedPeriods(?array $blockedPeriods): void
    {
        $this->blockedPeriods = $blockedPeriods;
    }

    public function getRentalTimes(): ?array
    {
        return $this->rentalTimes;
    }

    public function setRentalTimes(?array $rentalTimes): void
    {
        $this->rentalTimes = $rentalTimes;
    }

    public function getPrice(): ?PriceCollection
    {
        return $this->price;
    }

    public function setPrice(?PriceCollection $price): void
    {
        $this->price = $price;
    }

    public function getDepositPrice(): ?PriceCollection
    {
        return $this->depositPrice;
    }

    public function setDepositPrice(?PriceCollection $depositPrice): void
    {
        $this->depositPrice = $depositPrice;
    }

    public function getCheapestPrice(): CheapestPrice|CheapestPriceContainer|null
    {
        return $this->cheapestPrice;
    }

    public function setCheapestPrice(?CheapestPrice $cheapestPrice): void
    {
        $this->cheapestPrice = $cheapestPrice;
    }

    public function setCheapestPriceContainer(CheapestPriceContainer $container): void
    {
        $this->cheapestPriceContainer = $container;
    }

    public function getCheapestPriceContainer(): ?CheapestPriceContainer
    {
        return $this->cheapestPriceContainer;
    }

    public function getPrices(): RentalProductPriceCollection
    {
        return $this->prices;
    }

    public function setPrices(RentalProductPriceCollection $prices): void
    {
        $this->prices = $prices;
    }

    public function getDepositPrices(): RentalProductDepositPriceCollection
    {
        return $this->depositPrices;
    }

    public function setDepositPrices(RentalProductDepositPriceCollection $depositPrices): void
    {
        $this->depositPrices = $depositPrices;
    }

    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    public function setProduct(ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getOriginalStock(): ?int
    {
        return $this->originalStock;
    }

    public function setOriginalStock(?int $originalStock): void
    {
        $this->originalStock = $originalStock;
    }

    public function getBail(): ?array
    {
        return $this->bail;
    }

    public function setBail(?array $bail): void
    {
        $this->bail = $bail;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getParent(): ?RentalProductEntity
    {
        return $this->parent;
    }

    public function setParent(?RentalProductEntity $parent): void
    {
        $this->parent = $parent;
    }

    public function getChildren(): ?RentalProductCollection
    {
        return $this->children;
    }

    public function setChildren(RentalProductCollection $children): void
    {
        $this->children = $children;
    }

    public function getBailTaxId(): ?string
    {
        return $this->bailTaxId;
    }

    public function setBailTaxId(?string $bailTaxId): void
    {
        $this->bailTaxId = $bailTaxId;
    }

    public function getBailPrice(): ?PriceCollection
    {
        return $this->bailPrice;
    }

    public function setBailPrice(?PriceCollection $bailPrice): void
    {
        $this->bailPrice = $bailPrice;
    }

    public function getBailActive(): ?bool
    {
        return $this->bailActive;
    }

    public function setBailActive(?bool $bailActive): void
    {
        $this->bailActive = $bailActive;
    }

    public function getBailtax(): ?TaxEntity
    {
        return $this->bailtax;
    }

    public function setBailtax(TaxEntity $bailtax): void
    {
        $this->bailtax = $bailtax;
    }

    public function getTax(): ?TaxEntity
    {
        return $this->tax;
    }

    public function setTax(?TaxEntity $tax): void
    {
        $this->tax = $tax;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function getChildCount(): ?int
    {
        return $this->childCount;
    }

    public function setChildCount(?int $childCount): void
    {
        $this->childCount = $childCount;
    }
}
