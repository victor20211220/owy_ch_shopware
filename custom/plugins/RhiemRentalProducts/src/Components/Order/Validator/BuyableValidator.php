<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\Order\Validator;

use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductNotBuyableException;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Validator\Validator;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

class BuyableValidator extends Validator
{
    public function validate(array $params): void
    {
        /**
         * @var Cart $cart
         */
        $cart = $params['cart'];
        $lineItems = $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        if ($lineItems->count() !== 0) {
            $cartData = $cart->getData();
            $ids = [];
            foreach ($lineItems->getElements() as $lineItem) {
                /**
                 * @var SalesChannelProductEntity $product
                 */
                $product = $cartData->get('product-' . $lineItem->getId());
                if (!($product instanceof SalesChannelProductEntity)) {
                    continue;
                }

                $ids[$product->getId()] = $lineItem->getId();
            }

            if ($ids === []) {
                return;
            }

            /**
             * @var EntityRepository $rentalProductRepository
             */
            $rentalProductRepository = $params['rentalProductRepository'];
            /**
             * @var Context $context
             */
            $context = $params['context'];
            $criteria = new Criteria();
            $criteria->addFilter(
                new MultiFilter(
                    'AND',
                    [
                        new EqualsAnyFilter('productId', array_keys($ids)),
                        new EqualsFilter('active', true),
                    ]
                )
            );
            $notBuyableRentalProducts = $rentalProductRepository->search($criteria, $context)->getElements();
            /**
             * @var RentalProductEntity $rentalProduct
             */
            foreach ($notBuyableRentalProducts as $rentalProduct) {
                $lineItemId = $ids[$rentalProduct->getProductId()];
                /**
                 * @var SalesChannelProductEntity $product
                 */
                $product = $cartData->get('product-' . $lineItemId);
                $exception = new RentalProductNotBuyableException(
                    $product->getId(),
                    $product->getTranslation('name')
                );
                $cart->addErrors($exception);
                $cart->remove($lineItemId);
            }
        }
    }
}
