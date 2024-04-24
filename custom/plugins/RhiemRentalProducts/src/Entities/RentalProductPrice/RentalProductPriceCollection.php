<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Entities\RentalProductPrice;

use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceRuleCollection;

/**
 * @method void                          add(RentalProductPriceEntity $entity)
 * @method void                          set(string $key, RentalProductPriceEntity $entity)
 * @method RentalProductPriceEntity[]    getIterator()
 * @method RentalProductPriceEntity[]    getElements()
 * @method RentalProductPriceEntity|null get(string $key)
 * @method RentalProductPriceEntity|null first()
 * @method RentalProductPriceEntity|null last()
 */
class RentalProductPriceCollection extends PriceRuleCollection
{
    public function getApiAlias(): string
    {
        return 'rental_product_price_collection';
    }

    public function filterByRuleId(string $ruleId): self
    {
        return $this->filter(static fn(RentalProductPriceEntity $price) => $ruleId === $price->getRuleId());
    }

    public function sortByQuantity(): void
    {
        $this->sort(static fn(RentalProductPriceEntity $a, RentalProductPriceEntity $b) => $a->getQuantityStart() <=> $b->getQuantityStart());
    }

    protected function getExpectedClass(): string
    {
        return RentalProductPriceEntity::class;
    }
}
