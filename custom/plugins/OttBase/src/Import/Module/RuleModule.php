<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

use Shopware\Core\Framework\Rule\Rule;

final class RuleModule extends BaseModule
{
    private array $ruleIdCache = [];

    public function selectRuleId(string $name): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->ruleIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM rule WHERE name = :name COLLATE utf8mb4_bin
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->ruleIdCache[$name] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->ruleIdCache[$name] : (string) $result;
    }

    public function storeRuleCondition(
        string $id,
        string $ruleId,
        string $type,
        array $value,
        int $position = 0,
        ?string $parentId = null,
        ?string $scriptId = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO rule_condition (id, type, position, script_id, rule_id, parent_id, value, custom_fields, created_at)
            VALUES (UNHEX(:id), :type, :position, UNHEX(:scriptId), UNHEX(:ruleId), UNHEX(:parentId), :value, :customFields, NOW())
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('type', $type, \PDO::PARAM_STR);
        $preparedStatement->bindValue('position', $position, \PDO::PARAM_INT);
        $preparedStatement->bindValue('scriptId', $scriptId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('ruleId', $ruleId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('parentId', $parentId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('value', json_encode($value, \JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $preparedStatement->bindValue('customFields', empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));
        $preparedStatement->executeStatement();
    }

    public function storeRule(
        string $id,
        string $name,
        Rule $rule,
        ?string $description = null,
        ?int $priority = 100,
        ?array $areas = null,
        ?array $moduleTypes = null,
        ?array $customFields = null,
        ?bool $invalid = false
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO rule (id, name, description, priority, payload, invalid, areas, module_types, custom_fields, created_at)
            VALUES (UNHEX(:id), :name, :description, :priority, :payload, :invalid, :areas, :moduleTypes, :customFields, NOW())
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);
        $preparedStatement->bindValue('description', $description, \PDO::PARAM_STR);
        $preparedStatement->bindValue('priority', $priority, \PDO::PARAM_INT);
        $preparedStatement->bindValue('invalid', $invalid, \PDO::PARAM_INT);
        $preparedStatement->bindValue('payload', null === $rule ? null : serialize($rule));
        $preparedStatement->bindValue('moduleTypes', null === $moduleTypes ? null : json_encode($moduleTypes, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('areas', null === $areas ? null : json_encode($areas, \JSON_THROW_ON_ERROR));
        $preparedStatement->bindValue('customFields', empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR));
        $preparedStatement->executeStatement();
    }

    public function resetRuleConditions(string $ruleId): void
    {
        $statement = <<<'SQL'
            DELETE FROM rule_condition WHERE rule_id = UNHEX(:ruleId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('ruleId', $ruleId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }
}
