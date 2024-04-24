<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\LineItem;

use Shopware\Core\Framework\Context;
use Shopware\Core\Content\Product\State;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\PriceDefinitionFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Product\Cart\ProductCartProcessor;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;

class RentalProductLineItemFactory extends ProductLineItemFactory implements RentalProductModeInterface
{
    /**
     * @var string
     */
    final public const TYPE = 'rentalProduct';

    private readonly EntityRepository $rentalProductRepository;

    public function __construct(
        PriceDefinitionFactory $priceDefinitionFactory,
        EntityRepository $rentalProductRepository
    ) {
        $this->rentalProductRepository = $rentalProductRepository;

        parent::__construct($priceDefinitionFactory);
    }

    /**
     * @throws \League\Period\Exception|\Exception
     */
    public function create(array $data, SalesChannelContext $context): LineItem
    {
        if (empty($data['rentPeriod'])) {
            throw new \Exception('rentPeriod missing.');
        }

        $hash = md5((string) $data['rentPeriod']);
        $id = $data['id'] . '.rental.' . $hash;
        $lineItem = new LineItem(
            $id,
            self::TYPE,
            $data['referencedId'] ?? null,
            (int) $data['quantity'] ?? 1
        );
        $lineItem->markModified();
        $lineItem->setRemovable(true);
        $lineItem->setStackable(true);
        $lineItem->setStates([State::IS_PHYSICAL]);

        $this->addRentalTime($lineItem, $data['rentPeriod'], $context->getContext());

        $lineItem->addExtension(ProductCartProcessor::CUSTOM_PRICE, new ArrayEntity());
        $this->update($lineItem, $data, $context);

        return $lineItem;
    }

    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function addRentalTime($lineItem, $rentalPeriodString, Context $context)
    {
        $payload = $lineItem->getPayloadValue('rentalProduct') ?? [];
        $rentPeriod = $this->extractRentalPeriod($rentalPeriodString);
        $rentalProduct = $this->getRentalProduct($lineItem->getReferencedId(), $context);

        if (!$rentalProduct instanceof RentalProductEntity) {
            throw new \Exception('RentalProduct not found.');
        }

        $payload['rentalTime'] = RentalTime::createRentalTime(
            $lineItem->getReferencedId(),
            $lineItem->getQuantity(),
            $rentPeriod['start'],
            $rentPeriod['end'],
            'rent',
            $rentalProduct->getMode()
        );

        $lineItem->setPayloadValue('rentalProduct', $payload);
        $lineItem->markModified();
    }

    /**
     * @return EntityRepository
     */
    public function getRentalProductRepository()
    {
        return $this->rentalProductRepository;
    }

    private function getRentalProduct(string $productId, ?Context $context = null): ?RentalProductEntity
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('productId', $productId))
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('product');

        return $this->rentalProductRepository->search($criteria, $context)->first();
    }

    private function extractRentalPeriod($rentalPeriodString)
    {
        $rentPeriod = [];
        preg_match_all(
            '#\d{4}-[0-1]\d\-\d{2}T\d{2}:\d{2}:\d{2}#',
            (string) $rentalPeriodString,
            $matches
        );

        $rentPeriod['start'] = $matches[0][0];

        $rentPeriod['end'] = empty($matches[0][1]) ? $rentPeriod['start'] : $matches[0][1];

        return $rentPeriod;
    }
}
