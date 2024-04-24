<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Acris\StoreLocator\Custom\Aggregate\StoreLocatorRuleDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class RuleExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'acrisStoreLocator',
                StoreLocatorDefinition::class,
                StoreLocatorRuleDefinition::class,
                'rule_id',
                'store_locator_id'
            ))->addFlags(new ApiAware())
        );
    }

    public function getDefinitionClass(): string
    {
        return RuleDefinition::class;
    }
}
