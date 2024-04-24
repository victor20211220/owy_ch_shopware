<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Entities\RentalProductDepositPrice;

use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceRuleEntity;

class RentalProductDepositPriceEntity extends PriceRuleEntity
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
}
