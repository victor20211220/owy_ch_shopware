<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

final class PaymentMethodModule extends BaseModule
{
    private array $paymentMethodIdCache = [];

    public function selectPaymentMethodId(string $name): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->paymentMethodIdCache[$name])) {
            $statement = <<<'SQL'
                SELECT HEX(payment_method_id) FROM payment_method_translation WHERE name = :name COLLATE utf8mb4_bin
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('name', $name, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->paymentMethodIdCache[$name] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->paymentMethodIdCache[$name] : (string) $result;
    }

    public function storePaymentMethod(
        string $id,
        string $handlerIdentifier,
        int $position,
        bool $active,
        ?string $availabilityRuleId = null,
        ?string $pluginId = null,
        ?string $mediaId = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO payment_method (id, handler_identifier, position, active, availability_rule_id, plugin_id, media_id, created_at)
            VALUES (UNHEX(:id), :handler, :position, :active, UNHEX(:availabilityRuleId), UNHEX(:pluginId), UNHEX(:mediaId), NOW())
            ON DUPLICATE KEY UPDATE handler_identifier = :handler, position = :position, active = :active, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('handler', $handlerIdentifier, \PDO::PARAM_STR);
        $preparedStatement->bindValue('availabilityRuleId', $availabilityRuleId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('pluginId', $pluginId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('mediaId', $mediaId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('position', $position, \PDO::PARAM_INT);
        $preparedStatement->bindValue('active', $active, \PDO::PARAM_INT);
        $preparedStatement->executeStatement();
    }

    public function storePaymentMethodTranslation(
        string $paymentMethodId,
        string $languageId,
        ?string $name = null,
        ?string $description = null,
        ?array $customFields = null
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO payment_method_translation (payment_method_id, language_id, name, description, custom_fields, created_at)
            VALUES (UNHEX(:paymentMethodId), UNHEX(:languageId), :name, :description, :customFields, NOW())
            ON DUPLICATE KEY UPDATE name = :name, description = :description, custom_fields = :customFields, updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('paymentMethodId', $paymentMethodId, \PDO::PARAM_STR);
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
}
