<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Entities\RentalProductPrice;

use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceRuleEntity;

class RentalProductPriceEntity extends PriceRuleEntity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $rentalProductId;

    /**
     * @var RentalProductEntity|null
     */
    protected $rentalProduct;

    /**
     * @var RuleEntity|null
     */
    protected $rule;

    /**
     * @var int
     */
    protected $quantityStart;

    /**
     * @var int|null
     */
    protected $quantityEnd;

    /**
     * @var int
     */
    protected $mode;

    public function getRentalProductId(): string
    {
        return $this->rentalProductId;
    }

    public function setRentalProductId(string $rentalProductId): void
    {
        $this->rentalProductId = $rentalProductId;
    }

    public function getRentalProduct(): ?RentalProductEntity
    {
        return $this->rentalProduct;
    }

    public function setRentalProduct(?RentalProductEntity $rentalProduct): void
    {
        $this->rentalProduct = $rentalProduct;
    }

    public function getRule(): ?RuleEntity
    {
        return $this->rule;
    }

    public function setRule(?RuleEntity $rule): void
    {
        $this->rule = $rule;
    }

    public function getQuantityStart(): int
    {
        return $this->quantityStart;
    }

    public function setQuantityStart(int $quantityStart): void
    {
        $this->quantityStart = $quantityStart;
    }

    public function getQuantityEnd(): ?int
    {
        return $this->quantityEnd;
    }

    public function setQuantityEnd(?int $quantityEnd): void
    {
        $this->quantityEnd = $quantityEnd;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }
}
