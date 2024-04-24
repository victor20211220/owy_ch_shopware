<?php declare(strict_types=1);

namespace Ott\Base\Twig;

use Shopware\Core\Content\Category\Tree\TreeItem;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FindCategoryInTreeByIdExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('findCategoryInTreeById', fn (string $categoryId, array $tree): ?\Shopware\Core\Content\Category\Tree\TreeItem => $this->findCategoryInTreeById($categoryId, $tree)),
        ];
    }

    /*
     * Iterate the given navigation tree to find category by given id
     */
    public function findCategoryInTreeById(string $categoryId, array $tree): ?TreeItem
    {
        if (isset($tree[$categoryId])) {
            return $tree[$categoryId];
        }

        foreach ($tree as $item) {
            if ($categoryId === $item->getCategory()->getId()) {
                return $item;
            }

            $nested = $this->findCategoryInTreeById($categoryId, $item->getChildren());

            if (null !== $nested) {
                return $nested;
            }
        }

        return null;
    }
}
