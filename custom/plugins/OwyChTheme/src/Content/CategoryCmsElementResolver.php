<?php declare(strict_types=1);

namespace OwyChTheme\Content;

use OwyChTheme\Content\ShopPageStruct;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CategoryCmsElementResolver extends AbstractCmsElementResolver
{
    private const CATEGORY_BOXES_ENTITY_FALLBACK = 'owy-shoppage-nav-entity-fallback';
    private const STATIC_SEARCH_KEY = 'owy-shoppage-nav';

    public function getType(): string
    {
        return 'owy-shoppage-nav';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();

        $collection = new CriteriaCollection();

        if (!$categories = $config->get('categories')) {
            return null;
        }

        if ($categories->isStatic() && $categories->getValue()) {
            $criteria = new Criteria($categories->getValue());
            $criteria->addAssociation('children');


            $collection->add(self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier(),  CategoryDefinition::class, $criteria);
        }

        if ($categories->isMapped() && $categories->getValue() && $resolverContext instanceof EntityResolverContext) {
            if ($criteria = $this->collectByEntity($resolverContext, $categories)) {
                $collection->add(self::CATEGORY_BOXES_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier(), CategoryDefinition::class, $criteria);
            }
        }

        return $collection->all() ? $collection : null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();

        $boxes = new ShopPageStruct();
        $slot->setData($boxes);

        if (!$categoryConfig = $config->get('categories')) {
            return;
        }

        if ($categoryConfig->isStatic()) {
            $this->enrichFromSearch($boxes, $result, self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier());
        }

        if ($categoryConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $categories = $this->resolveEntityValue($resolverContext->getEntity(), $categoryConfig->getValue());
            if (!$categories) {
                $this->enrichFromSearch($boxes, $result, self::CATEGORY_BOXES_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier());
            } else {
                $boxes->setCategories($categories);
            }
        }
    }

    private function enrichFromSearch(ShopPageStruct $boxes, ElementDataCollection $result, string $searchKey): void
    {
        $searchResult = $result->get($searchKey);
        if (!$searchResult) {
            return;
        }

        /** @var CategoryCollection|null $categories */
        $categories = $searchResult->getEntities();

        if (!$categories) {
            return;
        }

        $boxes->setCategories($categories);
    }

    private function collectByEntity(EntityResolverContext $resolverContext, FieldConfig $config): ?Criteria
    {
        $entityCategories = $this->resolveEntityValue($resolverContext->getEntity(), $config->getValue());
        if ($entityCategories) {
            return null;
        }

        $criteria = $this->resolveCriteriaForLazyLoadedRelations($resolverContext, $config);
        $criteria->addAssociation('cover');

        return $criteria;
    }
}
