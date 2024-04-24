<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Component;

class CategoryTreeBuilder
{
    public static function getCategoryPath(array $categoryTree, array $categoryIds): array
    {
        $categories = [];

        foreach ($categoryIds as $categoryId) {
            if (empty($categoryId)) {
                continue;
            }

            $categoryNames = [];
            $category = self::processCategoryTree($categoryTree, $categoryId);

            while (null !== $category) {
                $categoryNames[] = $category['name'];
                if (null === $category['parent']) {
                    break;
                }
                $category = self::processCategoryTree($categoryTree, $category['parent']);
            }

            $categories[] = implode('|', array_reverse($categoryNames));
        }

        return $categories;
    }

    private static function processCategoryTree(array $categories, string $categoryId): ?array
    {
        foreach ($categories as $id => $category) {
            if ($categoryId === (string) $id) {
                return $category;
            }
            if (!empty($category['childs'])) {
                $childCategory = static::processCategoryTree($category['childs'], $categoryId);
                if (null !== $childCategory) {
                    return $childCategory;
                }
            }
        }

        return null;
    }

    public static function buildTree(array $categoryNodes): array
    {
        $categoryTree = [];
        while (!empty($categoryNodes)) {
            foreach ($categoryNodes as $key => $categoryNode) {
                if (empty($categoryNode['Parent'])) {
                    $categoryTree[$categoryNode['Nummer']] = [
                        'parent' => null,
                        'childs' => [],
                        'name'   => $categoryNode['BezeichnungD'],
                    ];
                    unset($categoryNodes[$key]);
                } elseif (self::addChildCategory($categoryTree, $categoryNode)) {
                    unset($categoryNodes[$key]);
                }
            }
        }

        return $categoryTree;
    }

    private static function addChildCategory(array &$categoryTree, array $categoryNode): bool
    {
        $foundCategory = false;
        foreach ($categoryTree as $number => $category) {
            if ($number === $categoryNode['Parent']) {
                $categoryTree[$number]['childs'][$categoryNode['Nummer']] = [
                    'parent' => $categoryNode['Parent'],
                    'childs' => [],
                    'name'   => $categoryNode['BezeichnungD'],
                ];

                $foundCategory = true;
                break;
            }

            if (!empty($categoryTree[$number]['childs'])) {
                if (static::addChildCategory($categoryTree[$number]['childs'], $categoryNode)) {
                    $foundCategory = true;
                    break;
                }
            }
        }

        return $foundCategory;
    }
}
