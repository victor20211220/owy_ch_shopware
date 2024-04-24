<?php declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\DAL;

use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Content\Product\DataAbstractionLayer\AbstractCheapestPriceQuantitySelector;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;

/**
 * Allows project overrides to change cheapest price selection
 */
class RentalProductCheapestPriceQuantitySelector extends AbstractCheapestPriceQuantitySelector
{
    public function getDecorated(): AbstractCheapestPriceQuantitySelector
    {
        throw new DecorationPatternException(self::class);
    }

    public function add(QueryBuilder $query): void
    {
        $query->addSelect([
            'rental_price.quantity_start != 1 as is_ranged',
        ]);

        $query->andWhere('rental_price.quantity_end IS NULL');
    }
}
