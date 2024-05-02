<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct;

use Shopware\Core\Defaults;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryTime;
use Shopware\Core\Checkout\Cart\LineItem\QuantityInformation;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Shopware\Core\Content\Product\Cart\ProductGatewayInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Shopware\Core\Checkout\Cart\Exception\MissingLineItemPriceException;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductNotFoundException;

class RentalProductCartProcessor implements CartDataCollectorInterface, CartProcessorInterface
{
    /**
     * @var string
     */
    final public const TYPE = 'rentalProduct';

    private readonly EntityRepository $rentalProductRepository;

    private readonly QuantityPriceCalculator $calculator;

    private readonly ProductGatewayInterface $productGateway;

    private readonly RentalProductPriceCalculator $rentalProductPriceCalculator;

    private readonly RentalProductQuantityCalculator $rentalProductQuantityCalculator;

    public function __construct(
        EntityRepository $rentalProductRepository,
        QuantityPriceCalculator $calculator,
        ProductGatewayInterface $productGateway,
        RentalProductPriceCalculator $rentalProductPriceCalculator,
        RentalProductQuantityCalculator $rentalProductQuantityCalculator
    ) {
        $this->rentalProductRepository = $rentalProductRepository;
        $this->calculator = $calculator;
        $this->productGateway = $productGateway;
        $this->rentalProductPriceCalculator = $rentalProductPriceCalculator;
        $this->rentalProductQuantityCalculator = $rentalProductQuantityCalculator;
    }

    /**
     * @throws \Exception
     */
    public function collect(
        CartDataCollection $data,
        Cart $original,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $lineItems = $original->getLineItems()->filterFlatByType(self::TYPE);
        $ids = $this->getNotCompleted($data, $lineItems);
        if ($ids !== []) {
            $products = $this->productGateway->get($ids, $context);
            foreach ($products as $product) {
                $data->set('rentalProduct-' . $product->getId(), $product);
            }
        }

        foreach ($lineItems as $key => $lineItem) {
            if ($lineItem->isModified()) {
                $modified = $lineItem;
                /** @var RentalTime $rentalTime */
                $rentalTime = $modified->getPayloadValue('rentalProduct')['rentalTime'];
                $rentalTime->setQuantity($modified->getQuantity());
                $updatePayload['rentalTime'] = $rentalTime;
                $modified->setPayloadValue(
                    'rentalProduct',
                    array_replace_recursive($modified->getPayloadValue('rentalProduct'), $updatePayload)
                );

                unset($lineItems[$key]);
                array_unshift($lineItems, $modified);

                break;
            }
        }

        //Standard enrich process
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === 'rentalProduct') {
                if (empty($rentalProducts[$lineItem->getReferencedId()])) {
                    $criteria = new Criteria();
                    $criteria
                        ->addFilter(new EqualsFilter('productId', $lineItem->getReferencedId()))
                        ->addFilter(new EqualsFilter('active', true))
                        ->addAssociation('product')
                        ->addAssociation('children')
                        ->addAssociation('prices');

                    $rentalProducts[$lineItem->getReferencedId()] = $this->rentalProductRepository->search(
                        $criteria,
                        $context->getContext()
                    )->first();
                    if (!$rentalProducts[$lineItem->getReferencedId()] instanceof RentalProductEntity) {
                        $original->addErrors(
                            new RentalProductNotFoundException($lineItem->getReferencedId(), $lineItem->getLabel())
                        );
                        $original->getLineItems()->remove($lineItem->getId());

                        continue;
                    }
                }

                $this->enrich(
                    $original,
                    $lineItem,
                    $data,
                    $context,
                    $rentalProducts[$lineItem->getReferencedId()] ?: null
                );
            }
        }

        //check if rentalProduct quantity calculation should be made
        if ($lineItems !== [] && !empty($rentalProducts) && (!empty($modified) || $original->isModified())) {
            $this->rentalProductQuantityCalculator->calculateCartQuantities(
                $original,
                $lineItems,
                $rentalProducts,
                $context
            );
        }
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $lineItems = $original
            ->getLineItems()
            ->filterType(self::TYPE);

        foreach ($lineItems as $lineItem) {
            $definition = $lineItem->getPriceDefinition();

            if (!$definition instanceof QuantityPriceDefinition) {
                throw CartException::missingLineItemPrice($lineItem->getId());
            }

            $definition->setQuantity($lineItem->getQuantity());
            $lineItem->setPrice($this->calculator->calculate($definition, $context));
            $toCalculate->add($lineItem);
        }
    }

    private function getNotCompleted(CartDataCollection $data, array $lineItems): array
    {
        $ids = [];

        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $id = $lineItem->getReferencedId();
            $key = 'rentalProduct-' . $id;
            if ($data->has($key)) {
                continue;
            }

            if ($lineItem->isModified()) {
                $ids[] = $id;

                continue;
            }

            // already enriched?
            if ($this->isComplete($lineItem) && $data->has($key)) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    private function isComplete(LineItem $lineItem): bool
    {
        return $lineItem->getPriceDefinition() instanceof PriceDefinitionInterface
            && $lineItem->getLabel() !== null
            && $lineItem->getCover() instanceof MediaEntity
            && $lineItem->getDeliveryInformation() instanceof DeliveryInformation
            && $lineItem->getQuantityInformation() instanceof QuantityInformation;
    }

    private function enrich(
        Cart $cart,
        LineItem $lineItem,
        CartDataCollection $data,
        SalesChannelContext $context,
        ?RentalProductEntity $rentalProductEntity
    ): void {
        if(!($rentalProductEntity instanceof RentalProductEntity)) return;

        $product = $data->get('rentalProduct-' . $lineItem->getReferencedId());

        if (!$product instanceof SalesChannelProductEntity) {
            $cart->addErrors(new RentalProductNotFoundException($lineItem->getReferencedId(), $lineItem->getLabel()));
            $cart->getLineItems()->remove($lineItem->getId());

            return;
        }

        $lineItem->setLabel($product->getTranslation('name'));
        if ($product->getCover() instanceof ProductMediaEntity) {
            $lineItem->setCover($product->getCover()->getMedia());
        }

        $deliveryTime = null;
        if ($product->getDeliveryTime() instanceof DeliveryTimeEntity) {
            $deliveryTime = DeliveryTime::createFromEntity($product->getDeliveryTime());
        }

        $lineItem->setDeliveryInformation(
            new DeliveryInformation(
                (int) $product->getAvailableStock(),
                (float) $product->getWeight(),
                $product->getShippingFree(),
                $product->getRestockTime(),
                $deliveryTime,
                $product->getHeight(),
                $product->getWidth(),
                $product->getLength()
            )
        );

        $this->rentalProductPriceCalculator->calculateRentalProductLineItemPrice(
            $rentalProductEntity,
            $context,
            $lineItem,
            $product
        );

        $quantityInformation = new QuantityInformation();
        $quantityInformation->setMinPurchase(
            $product->getMinPurchase() ?? 1
        );
        $quantityInformation->setPurchaseSteps(
            $product->getPurchaseSteps() ?? 1
        );

        $lineItem->setQuantityInformation($quantityInformation);

        $payload = [
            'isCloseout' => $product->getIsCloseout(),
            'customFields' => $product->getCustomFields(),
            'createdAt' => $product->getCreatedAt()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'releaseDate' => $product->getReleaseDate() instanceof \DateTimeInterface ? $product->getReleaseDate()->format(
                Defaults::STORAGE_DATE_TIME_FORMAT
            ) : null,
            'isNew' => $product->isNew(),
            'markAsTopseller' => $product->getMarkAsTopseller(),
            // @deprecated tag:v6.4.0 - purchasePrice Will be removed in 6.4.0
            'purchasePrice' => $lineItem->getPrice(),
            'purchasePrices' => null,
            'productNumber' => $product->getProductNumber(),
            'manufacturerId' => $product->getManufacturerId(),
            'taxId' => $product->getTaxId(),
            'tagIds' => $product->getTagIds(),
            'categoryIds' => $product->getCategoryTree(),
            'propertyIds' => $product->getPropertyIds(),
            'optionIds' => $product->getOptionIds(),
            'options' => $this->getOptions($product),
            'stock' => $product->getAvailableStock(),
        ];

        $payload = array_replace_recursive($payload, $lineItem->getPayload());

        $lineItem->replacePayload($payload);
    }

    private function getOptions(SalesChannelProductEntity $product): array
    {
        $options = [];

        if (!$product->getOptions() instanceof PropertyGroupOptionCollection) {
            return $options;
        }

        foreach ($product->getOptions() as $option) {
            if (!$option->getGroup() instanceof PropertyGroupEntity) {
                continue;
            }

            $options[] = [
                'group' => $option->getGroup()->getTranslation('name'),
                'option' => $option->getTranslation('name'),
            ];
        }

        return $options;
    }
}
