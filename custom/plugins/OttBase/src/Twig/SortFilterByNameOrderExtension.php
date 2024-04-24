<?php declare(strict_types=1);

namespace Ott\Base\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SortFilterByNameOrderExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('sortFilterByNameOrder', [$this, 'filterByNameOrder']),
        ];
    }

    /*
     * Sort filter property array based on given order list
     *
     * e.g.
     * origin = [a, b, c, d]
     * list   = [d,c]
     *
     * result = [d, c, a, b]
     */
    public function filterByNameOrder(array $arrayToSort, array $orderList): array
    {
        usort($arrayToSort, function ($a, $b) use ($orderList): int {
            $pos_a = array_search(strtolower($a->getName()), $orderList);
            $pos_b = array_search(strtolower($b->getName()), $orderList);

            if (false === $pos_a && false === $pos_b) {
                return 0;
            }
            if (false === $pos_a) {
                return 1;
            }
            if (false === $pos_b) {
                return -1;
            }

            return $pos_a >= $pos_b ? 1 : -1;
        });

        return $arrayToSort;
    }
}
