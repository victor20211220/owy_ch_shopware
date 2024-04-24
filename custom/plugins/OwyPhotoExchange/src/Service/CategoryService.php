<?php declare(strict_types=1);

namespace OwyPhotoExchange\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class CategoryService
{
    private EntityRepository $categoryRepository;

    public function __construct(EntityRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getActiveCategories(Context $context) : EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("isActive", 1));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        return $this->categoryRepository->search($criteria, $context);
    }
}