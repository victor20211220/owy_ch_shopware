<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT32887;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Aggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Symfony\Component\HttpFoundation\Request;

class RequestCriteriaBuilderFixed extends RequestCriteriaBuilder
{
    public function handleRequest(Request $request, Criteria $criteria, EntityDefinition $definition, Context $context): Criteria
    {
        $criteria = parent::handleRequest($request, $criteria, $definition, $context);

        foreach ($criteria->getAggregations() as $aggregation) {
            $this->validateAggregation($aggregation);
        }

        return $criteria;
    }

    private function validateAggregation(Aggregation $aggregation): void
    {
        if (str_contains($aggregation->getName(), '`')) {
            throw new InvalidSQLInputException('Invalid aggregation name, should not contain backticks.');
        }

        if (method_exists($aggregation, 'getAggregation') && $aggregation->getAggregation()) {
            $this->validateAggregation($aggregation->getAggregation());
        }
    }
}
