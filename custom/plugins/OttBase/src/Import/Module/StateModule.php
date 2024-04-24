<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

final class StateModule extends BaseModule
{
    private array $stateIdCache = [];
    private array $stateMachineIdCache = [];
    private array $stateTransitionIdCache = [];

    public function selectStateId(string $technicalName, string $stateMachineId): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->stateIdCache[$stateMachineId . $technicalName])) {
            $statement = <<<'SQL'
                SELECT HEX(id)
                FROM state_machine_state
                WHERE technical_name = :technicalName COLLATE utf8mb4_bin
                AND state_machine_id = UNHEX(:stateMachineId)
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('technicalName', $technicalName, \PDO::PARAM_STR);
            $preparedStatement->bindValue('stateMachineId', $stateMachineId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->stateIdCache[$stateMachineId . $technicalName] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->stateIdCache[$stateMachineId . $technicalName] : (string) $result;
    }

    public function selectStateMachineId(string $technicalName): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->stateMachineIdCache[$technicalName])) {
            $statement = <<<'SQL'
                SELECT HEX(id) FROM state_machine WHERE technical_name = :technicalName COLLATE utf8mb4_bin
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('technicalName', $technicalName, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->stateMachineIdCache[$technicalName] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->stateMachineIdCache[$technicalName] : (string) $result;
    }

    public function selectStateTransitionId(
        string $actionName,
        string $stateMachineId,
        string $fromStateId,
        string $toStateId
    ): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->stateTransitionIdCache[$actionName])) {
            $statement = <<<'SQL'
                SELECT HEX(id)
                FROM state_machine_transition
                WHERE action_name = :actionName COLLATE utf8mb4_bin
                AND state_machine_id = :stateMachineId
                AND from_state_id = :fromStateId
                AND to_state_id = :toStateId
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('actionName', $actionName, \PDO::PARAM_STR);
            $preparedStatement->bindValue('stateMachineId', $stateMachineId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('fromStateId', $fromStateId, \PDO::PARAM_STR);
            $preparedStatement->bindValue('toStateId', $toStateId, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->stateTransitionIdCache[$actionName] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->stateTransitionIdCache[$actionName] : (string) $result;
    }

    public function storeState(
        string $id,
        string $stateMachineId,
        string $technicalName
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO state_machine_state (id, technical_name, state_machine_id, created_at)
            VALUES (UNHEX(:id), :technicalName, UNHEX(:stateMachineId), NOW())
            ON DUPLICATE KEY UPDATE technical_name = :technicalName, state_machine_id = UNHEX(:stateMachineId), updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('stateMachineId', $stateMachineId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('technicalName', $technicalName, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeStateTranslation(
        string $stateId,
        string $languageId,
        ?string $name = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO state_machine_state_translation (language_id, state_machine_state_id, name, custom_fields, created_at)
            VALUES (UNHEX(:languageId), UNHEX(:stateId), :name, :customFields, NOW())
            ON DUPLICATE KEY UPDATE name = :name, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('stateId', $stateId, \PDO::PARAM_STR);
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

    public function storeStateTransition(
        string $id,
        string $actionName,
        string $stateMachineId,
        string $fromStateId,
        string $toStateId,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO state_machine_transition (id, action_name, state_machine_id, from_state_id, to_state_id, custom_fields, created_at)
            VALUES (UNHEX(:id), :actionName, UNHEX(:stateMachineId), UNHEX(:fromStateId), UNHEX(:toStateId), :customFields, NOW())
            ON DUPLICATE KEY UPDATE action_name = :actionName,
                                    from_state_id = UNHEX(:fromStateId),
                                    to_state_id = UNHEX(:toStateId),
                                    custom_fields = :customFields,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('stateMachineId', $stateMachineId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('fromStateId', $fromStateId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('toStateId', $toStateId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('actionName', $actionName, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            null === $customFields
            ? null
            : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->executeStatement();
    }
}
