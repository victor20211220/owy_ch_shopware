<?php declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\DAL;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceContainer;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\Struct\ArrayStruct;

class RentalCheapestPriceContainer extends CheapestPriceContainer
{
    public function __construct(CheapestPriceContainer $cheapestPriceContainer)
    {
        $value = $cheapestPriceContainer->value;
        parent::__construct($value);
    }

    public function resolve(Context $context): ?CheapestPrice
    {
        $ruleIds = $context->getRuleIds();
        $ruleIds[] = null;

        $prices = [];

        foreach ($this->value as $group) {
            foreach ($ruleIds as $ruleId) {
                $price = $this->filterByRuleId($group, $ruleId);

                if ($price === null) {
                    continue;
                }

                $prices[] = $price;

                break;
            }
        }

        if ($prices === []) {
            return null;
        }

        $cheapest = array_shift($prices);

        $reference = $this->getPriceValue($cheapest, $context);

        $hasRange = (bool) $cheapest['is_ranged'];

        foreach ($prices as $price) {
            $current = $this->getPriceValue($price, $context);

            if ($current === null) {
                continue;
            }

            if ($current !== $reference || $price['is_ranged']) {
                $hasRange = true;
            }

            if ($current < $reference) {
                $reference = $current;
                $cheapest = $price;
            }
        }

        $object = new CheapestPrice();
        $object->setRuleId($cheapest['rule_id']);
        $object->setVariantId($cheapest['variant_id']);
        $object->setParentId($cheapest['parent_id']);
        $object->setHasRange($hasRange);
        $object->setPurchase($cheapest['purchase_unit'] ? (float) $cheapest['purchase_unit'] : null);
        $object->setReference($cheapest['reference_unit'] ? (float) $cheapest['reference_unit'] : null);
        $object->setUnitId($cheapest['unit_id'] ?? null);

        if (!empty($cheapest['isRentalPrice'])) {
            $object->addExtension('rentalProduct', new ArrayStruct(['isRentalPrice' => true]));
        }

        $prices = [];

        $blueprint = new Price('', 1, 1, true);

        foreach ($cheapest['price'] as $row) {
            $price = clone $blueprint;
            $price->setCurrencyId($row['currencyId']);
            $price->setGross((float) $row['gross']);
            $price->setNet((float) $row['net']);
            $price->setLinked((bool) $row['linked']);

            if (isset($row['listPrice'])) {
                $list = clone $blueprint;

                $list->setCurrencyId($row['currencyId']);
                $list->setGross((float) $row['listPrice']['gross']);
                $list->setNet((float) $row['listPrice']['net']);
                $list->setLinked((bool) $row['listPrice']['linked']);

                $price->setListPrice($list);
            }

            $prices[] = $price;
        }

        $object->setPrice(new PriceCollection($prices));

        return $object;
    }

    private function filterByRuleId(array $prices, ?string $ruleId): ?array
    {
        foreach ($prices as $price) {
            if ($price['rule_id'] === $ruleId) {
                return $price;
            }
        }

        return null;
    }

    private function getPriceValue(array $price, Context $context): ?float
    {
        $currency = $this->getCurrencyPrice($price['price'], $context->getCurrencyId());

        if (!$currency) {
            return null;
        }

        $value = $context->getTaxState() === CartPrice::TAX_STATE_GROSS ? $currency['gross'] : $currency['net'];

        if ($currency['currencyId'] !== $context->getCurrencyId()) {
            $value *= $context->getCurrencyFactor();
        }

        return (float) $value;
    }

    private function getCurrencyPrice(array $collection, string $currencyId, bool $fallback = true): ?array
    {
        foreach ($collection as $price) {
            if ($price['currencyId'] === $currencyId) {
                return $price;
            }
        }

        if (!$fallback) {
            return null;
        }

        return $this->getCurrencyPrice($collection, Defaults::CURRENCY, false);
    }
}
