<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct;

use Shopware\Core\System\Unit\UnitEntity;
use Shopware\Core\Checkout\Cart\Price\Struct\ListPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\ReferencePrice;
use DateTime;
use Shopware\Core\System\Unit\UnitCollection;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\ReferencePriceDefinition;
use Shopware\Core\Content\Product\SalesChannel\Price\ReferencePriceDto;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Shopware\Core\Content\Product\SalesChannel\Price\AbstractProductPriceCalculator;
use Rhiem\RhiemRentalProducts\Entities\RentalProductPrice\RentalProductPriceCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection as CalculatedPriceCollection;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CalculatedCheapestPrice;

class RentalProductPriceCalculator extends AbstractProductPriceCalculator implements RentalProductModeInterface, RentalProductPriceModeInterface
{
    private readonly EntityRepository $unitRepository;

    private readonly QuantityPriceCalculator $calculator;

    private readonly SystemConfigService $systemConfigService;

    private ?UnitCollection $units = null;

    public function __construct(
        EntityRepository $unitRepository,
        QuantityPriceCalculator $calculator,
        SystemConfigService $systemConfigService
    ) {
        $this->unitRepository = $unitRepository;
        $this->calculator = $calculator;
        $this->systemConfigService = $systemConfigService;
    }

    public function calculate(iterable $products, SalesChannelContext $context): void
    {
        $units = $this->getUnits($context);

        /** @var SalesChannelProductEntity $product */
        foreach ($products as $product) {
            $rentalProduct = $product->getExtension('rentalProduct');
            if (empty($rentalProduct)) {
                continue;
            }

            /** @var RentalProductEntity $rentalProduct */
            if ($rentalProduct->isActive()) {
                $this->calculatePrice($rentalProduct, $product, $context, $units);
                $this->calculateAdvancePrices($rentalProduct, $product, $context, $units);
            }

            $this->calculateCheapestPrice($rentalProduct, $product, $context, $units);
        }
    }

    public function getDecorated(): AbstractProductPriceCalculator
    {
        throw new DecorationPatternException(self::class);
    }

    public function calculateRentalProductLineItemPrice(
        RentalProductEntity $rentalProductEntity,
        SalesChannelContext $context,
        LineItem $lineItem,
        SalesChannelProductEntity $product
    ): void {
        /** @var RentalTime $rentalTime */
        $rentalTime = $lineItem->getPayloadValue('rentalProduct')['rentalTime'];
        $removeBlockedDays = $this->systemConfigService->get('RhiemRentalProducts.config.removeBlockedDays');

        if($removeBlockedDays) {
            $blockedWeekdays = $this->systemConfigService->get('RhiemRentalProducts.config.blockedWeekdays');
            $blockedDays = $this->systemConfigService->get('RhiemRentalProducts.config.blockedDays');
            $blockedDates = array_map(static fn($date) => new DateTime($date['id']), $blockedDays);

            $blockedDates = array_filter($blockedDates, static fn($date) => $date >= $rentalTime->getStartDate() && $date <= $rentalTime->getEndDate());
        }

        /** @var RentalTime $rentalTime */
        $rentalTime = $lineItem->getPayloadValue('rentalProduct')['rentalTime'];
        $rentPerUnit = 0;
        if ($rentalProductEntity->getMode() === self::DAYRENT) {
            foreach ($rentalTime->getPeriod()->getDatePeriod('1 DAY') as $day) {
                if($removeBlockedDays) {
                    $dateBlocked = false;

                    foreach($blockedDates as $blockedDate) {
                        if($day->format('Y-m-d') === $blockedDate->format('Y-m-d')) {
                            $dateBlocked = true;
                            break;
                        }
                    }

                    if($dateBlocked || in_array((int) date('w', $day->getTimestamp()), $blockedWeekdays)) {
                        continue;
                    }
                }

                ++$rentPerUnit;
            }
        }

        $quantity = $lineItem->getQuantity();

        $units = $this->getUnits($context);
        $reference = new ReferencePriceDto(null, null, null);
        $prices = $rentalProductEntity->getPrices();

        if (empty($prices->getElements())) {
            $priceDefinition = $this->buildDefinition(
                $rentalProductEntity,
                $rentalProductEntity->getPrice(),
                $context,
                $units,
                $reference,
                $rentPerUnit,
                $quantity
            );
        } else {
            $priceCalcQuantity = $quantity;
            $firstPrice = $prices->first();
            if ($firstPrice->getMode() === self::DURATION) {
                $priceCalcQuantity = $rentPerUnit;
            }

            $product->setExtensions(['rentalProduct' => $rentalProductEntity]);
            $this->calculate([$product], $context);

            $price = $product->getCalculatedPrice();
            foreach ($product->getCalculatedPrices() as $price) {
                if ($priceCalcQuantity <= $price->getQuantity()) {
                    break;
                }
            }

            $priceDefinition = $this->buildPriceDefinition($price, $rentPerUnit, $quantity);
        }

        $lineItem->setPriceDefinition($priceDefinition);
    }

    public function calculateRentalProductPrices(
        SalesChannelContext $context,
        SalesChannelProductEntity $salesChannelProduct
    ): void {
        $this->calculate([$salesChannelProduct], $context);
    }

    private function calculatePrice(
        RentalProductEntity $rentalProduct,
        SalesChannelProductEntity $product,
        SalesChannelContext $context,
        UnitCollection $units
    ): void {
        $reference = new ReferencePriceDto(null, null, null);

        $definition = $this->buildDefinition($rentalProduct, $rentalProduct->getPrice(), $context, $units, $reference);
        $price = $this->calculator->calculate($definition, $context);
        $price->addExtension('rentalProduct', new ArrayStruct(['isRentalPrice' => true]));

        $product->setCalculatedPrice($price);
    }

    private function calculateAdvancePrices(
        RentalProductEntity $rentalProduct,
        SalesChannelProductEntity $product,
        SalesChannelContext $context,
        UnitCollection $units
    ): void {
        if ($rentalProduct->getPrices()->count() === 0) {
            return;
        }

        $prices = $this->filterRulePrices($rentalProduct->getPrices(), $context);

        if (!$prices instanceof RentalProductPriceCollection) {
            $product->setCalculatedPrices(new CalculatedPriceCollection());

            return;
        }

        $prices->sortByQuantity();

        $reference = new ReferencePriceDto(null, null, null);

        $calculated = new CalculatedPriceCollection();

        $rentPerUnit = 1;
        foreach ($prices as $price) {
            $quantity = $price->getQuantityEnd() ?? $price->getQuantityStart();

            $definition = $this->buildDefinition(
                $rentalProduct,
                $price->getPrice(),
                $context,
                $units,
                $reference,
                $rentPerUnit,
                $quantity
            );
            $calculatedPrice = $this->calculator->calculate($definition, $context);
            $calculatedPrice->addExtension('rentalProduct', new ArrayStruct(['isRentalPrice' => true]));
            $calculated->add($calculatedPrice);
        }

        $calculated->addExtension('rentalProduct', new ArrayStruct(['isRentalPrice' => true]));
        $product->setCalculatedPrices($calculated);
    }

    private function calculateCheapestPrice(
        RentalProductEntity $rentalProduct,
        SalesChannelProductEntity $product,
        SalesChannelContext $context,
        UnitCollection $units
    ): void {
        $price = $rentalProduct->getCheapestPrice();

        if (!$price instanceof CheapestPrice) {
            $reference = new ReferencePriceDto(null, null, null);

            $definition = $this->buildDefinition(
                $rentalProduct,
                $rentalProduct->getPrice(),
                $context,
                $units,
                $reference
            );

            $cheapest = CalculatedCheapestPrice::createFrom(
                $this->calculator->calculate($definition, $context)
            );

            $cheapest->setHasRange($rentalProduct->getPrices()->count() > 1);

            $product->setCalculatedCheapestPrice($cheapest);

            return;
        }

        $reference = ReferencePriceDto::createFromCheapestPrice($price);

        $definition = $this->buildDefinition($rentalProduct, $price->getPrice(), $context, $units, $reference);

        $cheapest = CalculatedCheapestPrice::createFrom(
            $this->calculator->calculate($definition, $context)
        );

        $cheapest->setHasRange($price->hasRange());

        if (!empty($price->getExtension('rentalProduct'))) {
            $cheapest->addExtension('rentalProduct', new ArrayStruct(['isRentalPrice' => true]));
        }

        $product->setCalculatedCheapestPrice($cheapest);
    }

    private function buildDefinition(
        RentalProductEntity $rentalProduct,
        PriceCollection $prices,
        SalesChannelContext $context,
        UnitCollection $units,
        ReferencePriceDto $reference,
        int $rentPerUnit = 1,
        int $quantity = 1
    ): QuantityPriceDefinition {
        $price = $this->getPriceValue($prices, $context);

        $definition = new QuantityPriceDefinition(
            $price * $rentPerUnit,
            $context->buildTaxRules($rentalProduct->getTaxId()),
            $quantity
        );
        $definition->setReferencePriceDefinition(
            $this->buildReferencePriceDefinition($reference, $units)
        );
        $definition->setListPrice(
            $this->getListPrice($prices, $context)
        );

        return $definition;
    }

    private function getPriceValue(PriceCollection $price, SalesChannelContext $context): float
    {
        /** @var Price $currency */
        $currency = $price->getCurrencyPrice($context->getCurrencyId());

        $value = $this->getPriceForTaxState($currency, $context);

        if ($currency->getCurrencyId() !== $context->getCurrency()->getId()) {
            $value *= $context->getContext()->getCurrencyFactor();
        }

        return $value;
    }

    private function getPriceForTaxState(Price $price, SalesChannelContext $context): float
    {
        if ($context->getTaxState() === CartPrice::TAX_STATE_GROSS) {
            return $price->getGross();
        }

        return $price->getNet();
    }

    private function getListPrice(?PriceCollection $prices, SalesChannelContext $context): ?float
    {
        if (!$prices instanceof PriceCollection) {
            return null;
        }

        $price = $prices->getCurrencyPrice($context->getCurrency()->getId());
        if (!$price instanceof Price || !$price->getListPrice() instanceof Price) {
            return null;
        }

        $value = $this->getPriceForTaxState($price->getListPrice(), $context);

        if ($price->getCurrencyId() !== $context->getCurrency()->getId()) {
            $value *= $context->getContext()->getCurrencyFactor();
        }

        return $value;
    }

    private function buildReferencePriceDefinition(
        ReferencePriceDto $definition,
        UnitCollection $units
    ): ?ReferencePriceDefinition {
        if ($definition->getPurchase() === null || $definition->getPurchase() <= 0) {
            return null;
        }

        if ($definition->getUnitId() === null) {
            return null;
        }

        if ($definition->getReference() === null || $definition->getReference() <= 0) {
            return null;
        }

        if ($definition->getPurchase() === $definition->getReference()) {
            return null;
        }

        $unit = $units->get($definition->getUnitId());
        if (!$unit instanceof UnitEntity) {
            return null;
        }

        return new ReferencePriceDefinition(
            $definition->getPurchase(),
            $definition->getReference(),
            $unit->getTranslation('name')
        );
    }

    private function filterRulePrices(
        RentalProductPriceCollection $rules,
        SalesChannelContext $context
    ): ?RentalProductPriceCollection {
        foreach ($context->getRuleIds() as $ruleId) {
            $filtered = $rules->filterByRuleId($ruleId);

            if ((is_countable($filtered) ? \count($filtered) : 0) > 0) {
                return $filtered;
            }
        }

        return null;
    }

    private function getUnits(SalesChannelContext $context): UnitCollection
    {
        if ($this->units instanceof UnitCollection) {
            return $this->units;
        }

        /** @var UnitCollection $units */
        $units = $this->unitRepository
            ->search(new Criteria(), $context->getContext())
            ->getEntities();

        return $this->units = $units;
    }

    private function buildPriceDefinition(CalculatedPrice $price, int $rentPerUnit, int $quantity): QuantityPriceDefinition
    {
        $definition = new QuantityPriceDefinition($price->getUnitPrice() * $rentPerUnit, $price->getTaxRules(), $quantity);

        if ($price->getListPrice() instanceof ListPrice) {
            $definition->setListPrice($price->getListPrice()->getPrice());
        }

        if ($price->getReferencePrice() instanceof ReferencePrice) {
            $definition->setReferencePriceDefinition(
                new ReferencePriceDefinition(
                    $price->getReferencePrice()->getPurchaseUnit(),
                    $price->getReferencePrice()->getReferenceUnit(),
                    $price->getReferencePrice()->getUnitName()
                )
            );
        }

        return $definition;
    }
}
