<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProductBail;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Rhiem\RhiemRentalProducts\Components\RentalProductBail\Exception\RentalProductMissingBailException;

class RentalProductBailCartProcessor implements CartDataCollectorInterface, CartProcessorInterface
{
    /**
     * @var string
     */
    final public const TYPE = 'rentalProductBail';

    private readonly EntityRepository $rentalProductRepository;

    private readonly RentalProductBailPriceCalculator $rentalProductBailPriceCalculator;

    private readonly TranslatorInterface $translator;

    private readonly RequestStack $requestStack;

    public function __construct(
        EntityRepository $rentalProductRepository,
        RentalProductBailPriceCalculator $rentalProductBailPriceCalculator,
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        $this->rentalProductRepository = $rentalProductRepository;
        $this->rentalProductBailPriceCalculator = $rentalProductBailPriceCalculator;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    public function collect(
        CartDataCollection $data,
        Cart $original,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        if ($original->getErrors()->count() !== 0) {
            foreach ($original->getErrors() as $error) {
                if ($error instanceof RentalProductMissingBailException) {
                    return;
                }
            }
        }

        $bailLineItems = $original->getLineItems()->filterType(self::TYPE);
        $rentalProductLineItems = $original->getLineItems()->filterType('rentalProduct');
        
        foreach ($rentalProductLineItems as $rentalProductLineItem) {
            $criteria = new Criteria();
            $criteria
                ->addFilter(new EqualsFilter('productId', $rentalProductLineItem->getReferencedId()))
                ->addFilter(new EqualsFilter('active', true));
            $rentalProduct = $this->rentalProductRepository->search(
                $criteria,
                $context->getContext()
            )->first();
            if ($rentalProduct instanceof RentalProductEntity) {
                $bail['price'] = $rentalProduct->getBailPrice();
                $bail['taxId'] = $rentalProduct->getBailTaxId();
                if ($rentalProduct->getBailActive()) {
                    $bailItem = $bailLineItems->get($rentalProductLineItem->getId() . '.bail');
                    if (!$bailItem instanceof LineItem) {
                        $request = $this->requestStack->getMainRequest();
                        if (mb_strpos($request->getRequestUri(), '/checkout/order') !== false) {
                            /** @var SalesChannelProductEntity $salesChannelProductEntity */
                            $salesChannelProductEntity = $data->get(
                                'rentalProduct-' . $rentalProductLineItem->getReferencedId()
                            );
                            $exception = new RentalProductMissingBailException(
                                $salesChannelProductEntity->getId(),
                                $salesChannelProductEntity->getTranslation('name')
                            );
                            $original->addErrors(
                                $exception
                            );
                        }

                        $id = $rentalProductLineItem->getId() . '.bail';
                        $bailItem = new LineItem(
                            $id,
                            self::TYPE,
                            $rentalProductLineItem->getReferencedId(),
                            1
                        );
                        $bailItem->markModified();
                        $bailItem->setRemovable(false);
                        $bailItem->setStackable(false);
                        $bailItem->setGood(false);
                        $this->enrich($original, $bailItem, $bail, $context);
                        $original->add($bailItem);
                    }
                }
            }
        }

        foreach ($bailLineItems->getElements() as $lineItem) {
            /** @var SalesChannelProductEntity $salesChannelProductEntity */
            $salesChannelProductEntity = $data->get('rentalProduct-' . $lineItem->getReferencedId());
            /** @var RentalProductEntity $rentalProduct */
            $rentalProduct = $salesChannelProductEntity->getExtension('rentalProduct');
            $bail['price'] = $rentalProduct->getBailPrice();
            $bail['taxId'] = $rentalProduct->getBailTaxId();

            if (!$rentalProduct->getBailActive() || empty($rentalProduct)) {
                $original->getLineItems()->remove($lineItem->getId());

                continue;
            }

            $this->enrich($original, $lineItem, $bail, $context);
        }
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $bailLineItems = $original->getLineItems()->filterType(self::TYPE);
        $rentalProductLineItems = $original->getLineItems()->filterType('rentalProduct');

        foreach ($bailLineItems as $lineItem) {
            $rentalProductLineItemId = str_replace('.bail', '', $lineItem->getId());
            $rentalProductLineItem = $rentalProductLineItems->get($rentalProductLineItemId);
            if (!$rentalProductLineItem instanceof LineItem) {
                continue;
            }

            $definition = $lineItem->getPriceDefinition();
            if (!$definition instanceof QuantityPriceDefinition) {
                throw CartException::missingLineItemPrice($lineItem->getId());
            }

            $label = $this->translator->trans(
                'checkout.rental-product-bail-label',
                ['%product%' => $rentalProductLineItem->getLabel()]
            );

            $lineItem->setLabel($label . ' ' . $rentalProductLineItem->getPayloadValue('productNumber'));
            $toCalculate->add($lineItem);
        }
    }

    private function enrich(
        Cart $cart,
        LineItem $bailLineItem,
        array $bail,
        SalesChannelContext $context
    ): void {
        $rentalProductLineItemId = str_replace('.bail', '', $bailLineItem->getId());
        $rentalProductLineItem = $cart->getLineItems()->get($rentalProductLineItemId);

        if (!$rentalProductLineItem instanceof LineItem) {
            return;
        }

        $bailLineItem->setPayload([
            'optionIds' => $rentalProductLineItem->getPayloadValue('optionIds'),
            'options' => $rentalProductLineItem->getPayloadValue('options'),
        ]);

        $this->rentalProductBailPriceCalculator->calculateRentalProductBailLineItemPrice(
            $bail,
            $bailLineItem,
            $rentalProductLineItem,
            $context
        );
    }
}
