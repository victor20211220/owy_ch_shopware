<?php declare(strict_types=1);

namespace Ott\Base\Import\Module;

use Shopware\Core\Defaults;

final class NewsletterRecipientModule extends BaseModule
{
    private array $newsletterRecipientCache = [];

    public function selectNewsletterRecipientId(string $email): ?string
    {
        $result = '';
        if (!$this->isCacheEnabled() || !isset($this->newsletterRecipientCache[$email])) {
            $statement = <<<'SQL'
                SELECT LOWER(HEX(id)) FROM newsletter_recipient WHERE email = :email
                SQL;

            $preparedStatement = $this->connection->prepare($statement);
            $preparedStatement->bindValue('email', $email, \PDO::PARAM_STR);

            $result = $preparedStatement->executeQuery()->fetchOne();
            if (null === $result || false === $result) {
                return null;
            }

            if ($this->isCacheEnabled()) {
                $this->newsletterRecipientCache[$email] = (string) $result;
            }
        }

        return $this->isCacheEnabled() ? $this->newsletterRecipientCache[$email] : (string) $result;
    }

    public function storeNewsletterRecipient(
        string $id,
        string $email,
        string $salesChannelId,
        string $hash,
        string $status,
        ?string $title,
        ?string $firstName,
        ?string $lastName,
        ?string $street,
        ?string $zipCode,
        ?string $city,
        ?string $salutationId,
        string $languageId = Defaults::LANGUAGE_SYSTEM,
        ?array $customFields = null,
        \DateTimeInterface $confirmedAt = new \DateTime(),
        \DateTimeInterface $createdAt = new \DateTime(),
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO newsletter_recipient (id, email, title, first_name, last_name, zip_code, city, street, salutation_id, language_id, sales_channel_id, custom_fields, hash, status, confirmed_at, created_at)
            VALUES (UNHEX(:id), :email, :title, :firstName, :lastName, :zipCode, :city, :street, UNHEX(:salutationId), UNHEX(:languageId), UNHEX(:salesChannelId), :customFields, :hash, :status, :confirmedAt, :createdAt)
            ON DUPLICATE KEY UPDATE email = :email,
                                    title = :title,
                                    first_name = :firstName,
                                    last_name = :lastName,
                                    zip_code = :zipCode,
                                    city = :city,
                                    street = :street,
                                    salutation_id = UNHEX(:salutationId),
                                    language_id = UNHEX(:languageId),
                                    sales_channel_id = UNHEX(:salesChannelId),
                                    custom_fields = :customFields,
                                    hash = :hash,
                                    status = :status,
                                    confirmed_at = :confirmedAt,
                                    updated_at = NOW()
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('id', $id, \PDO::PARAM_STR);
        $preparedStatement->bindValue('email', $email, \PDO::PARAM_STR);
        $preparedStatement->bindValue('title', $title, \PDO::PARAM_STR);
        $preparedStatement->bindValue('firstName', $firstName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('lastName', $lastName, \PDO::PARAM_STR);
        $preparedStatement->bindValue('zipCode', $zipCode, \PDO::PARAM_STR);
        $preparedStatement->bindValue('city', $city, \PDO::PARAM_STR);
        $preparedStatement->bindValue('street', $street, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salutationId', $salutationId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('languageId', $languageId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue(
            'customFields',
            empty($customFields) ? null : json_encode($customFields, \JSON_THROW_ON_ERROR)
        );
        $preparedStatement->bindValue('hash', $hash, \PDO::PARAM_STR);
        $preparedStatement->bindValue('status', $status, \PDO::PARAM_STR);
        $preparedStatement->bindValue('confirmedAt', $confirmedAt->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $preparedStatement->bindValue('createdAt', $createdAt->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function storeNewsletterRecipientTag(
        string $recipientId,
        string $tagId
    ): void
    {
        $statement = <<<'SQL'
            INSERT INTO newsletter_recipient_tag (newsletter_recipient_id, tag_id)
            VALUES (UNHEX(:recipientId), UNHEX(:tagId))
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('tagId', $tagId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('recipientId', $recipientId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }

    public function resetNewsletterRecipientTag(
        string $recipientId
    ): void
    {
        $statement = <<<'SQL'
            DELETE FROM newsletter_recipient_tag WHERE newsletter_recipient_id = UNHEX(:recipientId)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('recipientId', $recipientId, \PDO::PARAM_STR);
        $preparedStatement->executeStatement();
    }
}
