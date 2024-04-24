<?php declare(strict_types=1);

namespace Ott\Base\Command;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Command\Lifecycle\AbstractPluginLifecycleCommand;
use Shopware\Core\Framework\Plugin\PluginLifecycleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginReinstallCommand extends AbstractPluginLifecycleCommand
{
    public CacheClearer $cacheClearer;
    private const LIFECYCLE_METHOD = 'install';
    private Connection $connection;

    public function __construct(
        PluginLifecycleService $pluginLifecycleService,
        EntityRepository $entityRepository,
        CacheClearer $cacheClearer,
        Connection $connection
    )
    {
        parent::__construct(
            $pluginLifecycleService,
            $entityRepository,
            $cacheClearer
        );

        $this->connection = $connection;
    }

    public function configure(): void
    {
        $this->setName('plugin:force-reinstall');
        $this->configureCommand(self::LIFECYCLE_METHOD);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $shopwareStyle = new ShopwareStyle($input, $output);
        $context = Context::createDefaultContext();
        $plugins = $this->prepareExecution(self::LIFECYCLE_METHOD, $shopwareStyle, $input, $context);

        if (null === $plugins) {
            return self::SUCCESS;
        }

        $activePlugins = $this->getAllActivePlugins();
        try {
            $this->updatePlugins($activePlugins, false);

            foreach ($plugins as $plugin) {
                // we need to execute the console cmds cause the project needs a fresh bootstraping after deactivating
                // which is not given in the PluginLifecycleService
                echo shell_exec(\PHP_BINARY . ' bin/console plugin:uninstall ' . $plugin->getName());
                echo shell_exec(\PHP_BINARY . ' bin/console plugin:install --activate ' . $plugin->getName());

                $shopwareStyle->text(
                    sprintf(
                        'Plugin "%s" has been installed%s successfully.',
                        $plugin->getName(),
                        ' and activated'
                    )
                );
            }

            if ($input->getOption('clearCache')) {
                $this->cacheClearer->clear();
            }
        } catch (\Exception $exception) {
            $output->writeln(
                sprintf(
                    'Some error occured [%s][%s]: %s',
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getMessage()
                )
            );
        } finally {
            $this->updatePlugins($activePlugins);
        }

        return Command::SUCCESS;
    }

    private function updatePlugins(array $ids, bool $active = true): void
    {
        $statement = <<<'SQL'
            UPDATE plugin SET active = :active WHERE id IN (%s)
            SQL;

        array_walk($ids, function (&$item): void {
            $item = sprintf('UNHEX(\'%s\')', $item['id']);
        });

        $preparedStatement = $this->connection->prepare(
            sprintf(
                $statement,
                trim(implode(',', $ids), ' ,')
            )
        );
        $preparedStatement->bindValue('active', $active, \PDO::PARAM_BOOL);
        $preparedStatement->executeStatement();
    }

    private function getAllActivePlugins(): array
    {
        $statement = <<<'SQL'
            SELECT LOWER(HEX(id)) as id, name FROM plugin WHERE active = 1
            SQL;

        $preparedStatement = $this->connection->prepare($statement);

        return $preparedStatement->executeQuery()->fetchAllAssociative();
    }
}
