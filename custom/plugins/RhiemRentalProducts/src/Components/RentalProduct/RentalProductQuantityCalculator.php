<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct;

use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTimeCollection;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Validator\Validator;
use Rhiem\RhiemRentalProducts\Components\RentalTime\ProductRentalTimeCollector;
use Rhiem\RhiemRentalProducts\Components\RentalTime\LineItemRentalTimeCollector;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Validator\RentQuantityValidator;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductRemoveException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductQuantityReduceException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductRentPeriodInvalidException;

class RentalProductQuantityCalculator
{
    private readonly LineItemRentalTimeCollector $lineItemRentalTimeCollector;

    private readonly ProductRentalTimeCollector $productRentalTimeCollector;

    private readonly RentalProductPriceCalculator $rentalProductPriceCalculator;

    private readonly RentQuantityValidator $rentQuantityValidator;

    private readonly iterable $preQuantityCalculationValidator;

    private readonly iterable $postQuantityCalculationValidator;

    public function __construct(
        LineItemRentalTimeCollector $lineItemRentalTimeCollector,
        ProductRentalTimeCollector $productRentalTimeCollector,
        RentalProductPriceCalculator $rentalProductPriceCalculator,
        RentQuantityValidator $rentQuantityValidator,
        iterable $preQuantityCalculationValidator,
        iterable $postQuantityCalculationValidator
    ) {
        $this->lineItemRentalTimeCollector = $lineItemRentalTimeCollector;
        $this->productRentalTimeCollector = $productRentalTimeCollector;
        $this->rentalProductPriceCalculator = $rentalProductPriceCalculator;
        $this->rentQuantityValidator = $rentQuantityValidator;
        $this->preQuantityCalculationValidator = $preQuantityCalculationValidator;
        $this->postQuantityCalculationValidator = $postQuantityCalculationValidator;
    }

    /**
     * @param LineItem[]            $lineItems
     * @param RentalProductEntity[] $rentalProducts
     *
     * @throws \Exception
     */
    public function calculateCartQuantities(
        Cart $original,
        array $lineItems,
        array $rentalProducts,
        SalesChannelContext $context
    ): void {
        foreach ($lineItems as $lineItem) {
            try {
                //Pre Validation
                /** @var Validator $preValidator */
                foreach ($this->preQuantityCalculationValidator as $preValidator) {
                    $preValidator->validate(
                        [
                            'lineItem' => $lineItem,
                            'rentalProduct' => $rentalProducts[$lineItem->getReferencedId()],
                            'cart' => $original
                        ]
                    );
                }

                //Quantity Calculation
                $payload['maxAvailable'] = $this->calculateMaxAvailableForLineItem(
                    $original,
                    $lineItem,
                    $rentalProducts[$lineItem->getReferencedId()],
                    $context->getContext()
                );
                $lineItem->setPayloadValue(
                    'rentalProduct',
                    array_replace_recursive($lineItem->getPayloadValue('rentalProduct'), $payload)
                );
                //Quantity Validation
                try {
                    $this->rentQuantityValidator->validate(
                        [
                            'lineItem' => $lineItem,
                            'rentalProduct' => $rentalProducts[$lineItem->getReferencedId()],
                        ]
                    );
                } catch (RentalProductQuantityReduceException $rentalProductQuantityReduceException) {
                    $original->addErrors($rentalProductQuantityReduceException);
                    $lineItem->setQuantity($payload['maxAvailable']);
                    $lineItemPayload = $lineItem->getPayloadValue('rentalProduct');
                    /** @var RentalTime $rentalTime */
                    $rentalTime = $lineItemPayload['rentalTime'];
                    $rentalTime->setQuantity($payload['maxAvailable']);
                    $lineItemPayload['rentalTime'] = $rentalTime;
                    $lineItem->setPayloadValue('rentalProduct', $lineItemPayload);
                    $product = $rentalProducts[$lineItem->getReferencedId()]->getProduct();
                    $salesChannelProduct = $original->getData()->get('rentalProduct-' . $product->getId());
                    $this->rentalProductPriceCalculator->calculateRentalProductLineItemPrice(
                        $rentalProducts[$lineItem->getReferencedId()],
                        $context,
                        $lineItem,
                        $salesChannelProduct
                    );
                }

                //Post Validation
                foreach ($this->postQuantityCalculationValidator as $postValidator) {
                    $postValidator->validate(
                        [
                            'lineItem' => $lineItem,
                            'rentalProduct' => $rentalProducts[$lineItem->getReferencedId()],
                            'cart' => $original
                        ]
                    );
                }
            } catch (RentalProductRemoveException $rentalProductRemoveException) {
                $original->remove($lineItem->getId());
                $original->addErrors($rentalProductRemoveException);
            }
        }
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function createRentTimesForProduct(
        Cart $cart,
        RentalProductEntity $rentalProductEntity,
        string $productId,
        Context $context
    ) {
        $rentalTimeCollection = $this->productRentalTimeCollector->collectRentalTimes(
            $cart,
            $rentalProductEntity,
            $productId,
            $context
        );

        if (!empty($rentalTimeCollection[$productId])) {
            return $rentalTimeCollection[$productId]->createRentTimes($rentalProductEntity);
        }

        return [];
    }

    /**
     * @throws \Exception
     */
    private function calculateMaxAvailableForLineItem(
        Cart $cart,
        LineItem $lineItem,
        RentalProductEntity $rentalProductEntity,
        Context $context
    ): int {
        /** @var RentalTimeCollection[] $rentalTimeCollection */
        $rentalTimeCollection = $this->lineItemRentalTimeCollector->collectRentalTimes(
            $cart,
            $lineItem,
            $rentalProductEntity,
            $context
        );
        if ($rentalTimeCollection === []) {
            $maxAvailable = $rentalProductEntity->getOriginalStock();
        } else {
            $maxAvailable = $rentalProductEntity->getOriginalStock()
                - $rentalTimeCollection[$lineItem->getReferencedId()]->getBlockedQuantityForRentalTimePeriod(
                    $lineItem,
                    $rentalProductEntity
                );
        }

        return $maxAvailable;
    }
}
