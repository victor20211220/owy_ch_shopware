<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;

final class ShippingMethodModule extends BaseModule
{
    private array $shippingMethodIdCache = [];
    private array $deliveryTimeIdCache = [];

    public function selectShippingMethodId(string $name): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->shippingMethodIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT HEX(sm.id)
                FROM shipping_method sm
                JOIN shipping_method_translation smt on sm.id = smt.shipping_method_id
                WHERE smt.name = :name COLLATE utf8mb4_bin
                AND smt.language_id = UNHEX(:languageId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
            $preparedStatement->bindValue('languageId', $this->getDefaultLanguageId(), \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->shippingMethodIdCache[$name] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->shippingMethodIdCache[$name] : (string) $result;
    }

    public function storeShippingMethod(
        string $id,
        bool $active,
        string $ruleId,
        ?string $mediaId = null,
        ?string $deliveryTimeId = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO shipping_method (id, active, availability_rule_id, media_id, delivery_time_id, created_at)
            VALUES (UNHEX(:id), :active, UNHEX(:availabilityRuleId), UNHEX(:mediaId), UNHEX(:deliveryTimeId), NOW())
            ON DUPLICATE KEY UPDATE active = :active,
                                    availability_rule_id = UNHEX(:availabilityRuleId),
                                    media_id = UNHEX(:mediaId),
                                    delivery_time_id = UNHEX(:deliveryTimeId),
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('active', $active, \PDO::PARAM_INT);
        $preparedStatement->bindValue('availabilityRuleId', $ruleId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('mediaId', $mediaId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('deliveryTimeId', $deliveryTimeId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeShippingMethodTranslation(
        string $shippingMethodId,
        string $languageId,
        ?string $name = null,
        ?string $description = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO shipping_method_translation (shipping_method_id, language_id, name, description, custom_fields, created_at)
            VALUES (UNHEX(:shippingMethodId), UNHEX(:languageId), :name, :description, :customFields, NOW())
            ON DUPLICATE KEY UPDATE name = :name, description = :description, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('shippingMethodId', $shippingMethodId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('description', $description, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function selectShippingPriceId(
        string $shippingMethodId,
        ?string $ruleId,
        ?string $calculationRuleId,
        float $start
    ): ?string
    {
        $statement = <<<'SQL'
            SELECT HEX(id)
            FROM shipping_method_price
            WHERE shipping_method_id = UNHEX(:shippingMethodId)
            %s
            %s
            AND quantity_start = :start
            SQL;

        $preparedStatement = $this->connection->prepare(
            sprintf(
                $statement,
                null === $ruleId ? 'AND rule_id IS NULL' : 'AND rule_id = UNHEX(:ruleId)',
                null === $calculationRuleId ? 'AND calculation_rule_id IS NULL' : 'AND calculation_rule_id = UNHEX(:calculationRuleId)'
            )
        );
        $preparedStatement->bindValue('shippingMethodId', $shippingMethodId, \PDO::PARAM_STR);
        if (null !== $ruleId) {
            $preparedStatement->bindValue('ruleId', $ruleId, \PDO::PARAM_STR);
        }
        if (null !== $calculationRuleId) {
            $preparedStatement->bindValue('calculationRuleId', $calculationRuleId, \PDO::PARAM_STR);
        }
        $preparedStatement->bindValue('start', $start);

        $result = $preparedStatement->executeQuery()->fetchOne();
        if (null === $result || false === $result) {
            return null;
        }

        return $result;
    }

    public function storeShippingPrice(
        string $id,
        string $shippingMethodId,
        PriceCollection $priceCollection,
        ?string $ruleId,
        ?string $calculationRuleId,
        float $start,
        ?float $end = null,
        int $calculation = 1,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO shipping_method_price (id, shipping_method_id, calculation, rule_id, currency_price, calculation_rule_id, quantity_start, quantity_end, custom_fields, created_at)
            VALUES (UNHEX(:id), UNHEX(:shippingMethodId), :calculation, UNHEX(:ruleId), :currencyPrice, UNHEX(:calculationRuleId), :start, :end, :customFields, NOW())
            ON DUPLICATE KEY UPDATE calculation = :calculation, currency_price = :currencyPrice, quantity_start = :start, quantity_end = :end, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('shippingMethodId', $shippingMethodId, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'currencyPrice',
            null === $priceCollection
                ? null
                : json_encode($this->convertPriceCollection($priceCollection), \JSON_THROW_ON_ERROR),
            \PDO::PARAM_STR
        );
        $preparedStatement->bindValue('ruleId', $ruleId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('calculationRuleId', $calculationRuleId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('calculation', $calculation, \PDO::PARAM_INT);
        $preparedStatement->bindValue('start', $start);
        $preparedStatement->bindValue('end', $end);
        $preparedStatement->bindValue(
            'customFields',
            empty($customFields)
                ? null
                : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }

    public function selectDeliveryTimeId(string $name): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->deliveryTimeIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT HEX(sm.id)
                FROM delivery_time sm
                JOIN delivery_time_translation smt on sm.id = smt.delivery_time_id
                WHERE smt.name = :name COLLATE utf8mb4_bin
                AND smt.language_id = UNHEX(:languageId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
            $preparedStatement->bindValue('languageId', $this->getDefaultLanguageId(), \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->deliveryTimeIdCache[$name] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->deliveryTimeIdCache[$name] : (string) $result;
    }

    public function storeDeliveryTime(
        string $id,
        int $min,
        int $max,
        string $unit
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO delivery_time (id, min, max, unit, created_at)
            VALUES (UNHEX(:id), :min, :max, :unit, NOW())
            ON DUPLICATE KEY UPDATE min = :min, max = :max, unit = :unit, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('min', $min, \PDO::PARAM_INT);
        $preparedStatement->bindValue('max', $max, \PDO::PARAM_INT);
        $preparedStatement->bindValue('unit', $unit, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeDeliveryTimeTranslation(
        string $deliveryTimeId,
        string $languageId,
        ?string $name = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO delivery_time_translation (delivery_time_id, language_id, name, custom_fields, created_at)
            VALUES (UNHEX(:deliveryTimeId), UNHEX(:languageId), :name, :customFields, NOW())
            ON DUPLICATE KEY UPDATE name = :name, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('deliveryTimeId', $deliveryTimeId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }
}
