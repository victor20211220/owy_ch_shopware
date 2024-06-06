<?php

namespace Swag\Security\Fixes\NEXT32886;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Commercial\FlowBuilder\WebhookFlowAction\Domain\Action\CallWebhookAction;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;
use Shopware\Core\Content\Media\File\FileUrlValidatorInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Webhook\EventLog\WebhookEventLogDefinition;

class CallWebhookActionFix extends CallWebhookAction
{
    private FileUrlValidatorInterface $fileUrlValidator;

    private LoggerInterface $logger;

    private Connection $connection;

    public function setFileUrlValidator(FileUrlValidatorInterface $fileUrlValidator): void
    {
        $this->fileUrlValidator = $fileUrlValidator;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    public function handleFlow(StorableFlow $flow): void
    {
        $config = $flow->getConfig();

        // when we dont have a baseUrl don't validate
        if (!isset($config['baseUrl'])) {
            parent::handleFlow($flow);

            return;
        }

        if (!$this->fileUrlValidator->isValid($config['baseUrl'])) {
            $this->logger->error('Webhook url is not valid: Webhook urls must be publicly accessible.');

            $webhookEventId = Uuid::randomBytes();
            $this->connection->executeStatement(
                'INSERT INTO
                `webhook_event_log` (id, delivery_status, timestamp, webhook_name, event_name, url, request_content, response_content, created_at)
                VALUES (:webhookEventId, :deliveryStatus, :timestamp, :webhookName, :eventName, :url, :requestContent, :responseContent, :createdAt)',
                [
                    'webhookEventId' => $webhookEventId,
                    'deliveryStatus' => WebhookEventLogDefinition::STATUS_FAILED,
                    'timestamp' => time(),
                    'webhookName' => $config['method'] . ': ' . $config['baseUrl'],
                    'eventName' => $flow->getName(),
                    'url' => $config['baseUrl'],
                    'requestContent' => '{}',
                    'responseContent' => \json_encode([
                        'message' => 'Webhook url is not valid: Webhook urls must be publicly accessible.',
                    ], JSON_THROW_ON_ERROR),
                    'createdAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ],
            );

            $this->connection->executeStatement(
                'INSERT INTO `swag_sequence_webhook_event_log` (sequence_id, webhook_event_log_id)
                VALUES (:sequenceId, :webhookEventId)',
                [
                    'sequenceId' => Uuid::fromHexToBytes($flow->getFlowState()->getSequenceId()),
                    'webhookEventId' => $webhookEventId,
                ]
            );

            return;
        }

        parent::handleFlow($flow);
    }
}
