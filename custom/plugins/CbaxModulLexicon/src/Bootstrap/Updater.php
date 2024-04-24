<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Bootstrap;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Doctrine\DBAL\Connection;

use Cbax\ModulLexicon\ScheduledTask\SeoIndex;

class Updater
{
    const PLUGIN = 'CbaxModulLexicon';

    public function updateData($container, $context)
    {
        $oldVersion = $context->getCurrentPluginVersion();
        $connection = $container->get(Connection::class);
        $db = new Database();

        try {
            if (version_compare($oldVersion, '1.0.7', '<=')) {

                $scheduledTaskRepository = $container->get('scheduled_task.repository');

                $criteria = new Criteria();

                $criteria->addFilter(new EqualsFilter('name', SeoIndex::getTaskName()));

                $task = $scheduledTaskRepository->search($criteria, $context->getContext())->first();

                if (empty($task)) return;

                $updatePayload = [];
                $updatePayload[] = ['id' => $task->getId(), 'runInterval' => 86400];

                $scheduledTaskRepository->update($updatePayload, $context->getContext());
            }

            if (version_compare($oldVersion, '3.1.3', '<')) {
                $tableName = 'cbax_lexicon_product';
                $columnName = 'created_at';

                $db->deleteColumn($tableName, $columnName, $connection);

                $columnName = 'updated_at';
                $db->deleteColumn($tableName, $columnName, $connection);
            }

            if (version_compare($oldVersion, '2.0.4', '<')) {

                $connection->executeStatement("ALTER TABLE `cbax_lexicon_entry_translation` MODIFY COLUMN `description` text DEFAULT NULL;");
                $connection->executeStatement("ALTER TABLE `cbax_lexicon_entry_translation` MODIFY COLUMN `description_long` text DEFAULT NULL;");
            }

            if (!$this->columnExist('cbax_lexicon_entry', 'media2_id', $connection)) {
                $connection->executeUpdate("ALTER TABLE `cbax_lexicon_entry` ADD `media2_id` BINARY(16) DEFAULT NULL AFTER `attribute2`;");
            }

            return;

        } catch (\Throwable $e) {

        }
    }

    public function afterUpdateSteps(array $services, $context)
    {
        $systemConfigService = $services['systemConfigService'];

        $oldVersion = $context->getCurrentPluginVersion();

        try {
            if (version_compare($oldVersion, '3.1.3', '<')) {
                $notification = array(
                    'title' => 'cbaxmodullexicon.updateNotification.default.titleImportantUpdate',
                    'message' => 'cbaxmodullexicon.updateNotification.default.message',
                    'action' => array(
                        'label' => 'cbaxmodullexicon.updateNotification.default.action',
                        'route' => 'sw.cms.index',
                        'params' => false,
                        'directLink' => false,
                        'multi' => false
                    )
                );

                $systemConfigService->set(self::PLUGIN . '.config.' . 'updateWithNotification',
                    $notification
                );
            }
        } catch (\Throwable $e) {
            // should only be needed in development
        }
    }

    /**
     * Internal helper function to check if a database table column exist.
     */
    public function columnExist(string $tableName, string $columnName, Connection $connection): bool
    {
        $sql = "SHOW COLUMNS FROM " . $tableName . " LIKE '" . $columnName ."'";

        return !empty($connection->fetchAssociative($sql));
    }
}
