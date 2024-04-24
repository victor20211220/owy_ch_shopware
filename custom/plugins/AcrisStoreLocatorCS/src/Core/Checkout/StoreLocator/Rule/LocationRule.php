<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Checkout\StoreLocator\Rule;

use Acris\Rules\Core\Content\Product\Cart\AcrisRulesCartProcessor;
use Acris\StoreLocator\Custom\StoreLocatorDefinition;
use Shopware\Core\Framework\Rule\Exception\UnsupportedValueException;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Framework\Rule\RuleComparison;
use Shopware\Core\Framework\Rule\RuleConfig;
use Shopware\Core\Framework\Rule\RuleConstraints;
use Shopware\Core\Framework\Rule\RuleScope;


class LocationRule extends Rule
{

    protected array $locationIds;
    protected string $operator;

    public function __construct(string $operator = self::OPERATOR_EQ, array $locationIds = [])
    {
        parent::__construct();

        $this->locationIds = $locationIds;
        $this->operator = $operator;
    }

    public function match(RuleScope $scope): bool
    {
        if ($this->locationIds === null) {
            throw new UnsupportedValueException(\gettype($this->locationIds), self::class);
        }

        return RuleComparison::uuids([$scope->getContext()->getLanguageId()], $this->locationIds, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'operator' => RuleConstraints::stringOperators(false),
            'locationIds' => RuleConstraints::uuids(),
        ];
    }

    public function getName(): string
    {
        return 'location';
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField('locationIds', StoreLocatorDefinition::ENTITY_NAME, true);
    }
}
