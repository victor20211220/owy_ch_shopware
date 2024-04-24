<?php declare(strict_types=1);

namespace Ott\Base\Command;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Storefront\Theme\ConfigLoader\AbstractAvailableThemeProvider;
use Shopware\Storefront\Theme\ThemeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ThemeCompileCommand extends Command
{
    private ThemeService $themeService;
    private AbstractAvailableThemeProvider $themeProvider;
    private Connection $connection;

    public function __construct(
        ThemeService $themeService,
        AbstractAvailableThemeProvider $themeProvider,
        Connection $connection,
        ?string $name = null
    )
    {
        parent::__construct($name);
        $this->themeService = $themeService;
        $this->themeProvider = $themeProvider;
        $this->connection = $connection;
    }

    public function configure(): void
    {
        $this
            ->setName('ott:theme:compile')
            ->addOption('keep-assets', 'k', InputOption::VALUE_NONE, 'Keep current assets, do not delete them')
            ->addOption('active-only', 'a', InputOption::VALUE_NONE, 'Compile themes only for active  sales channels')
            ->addArgument(
                'sales-channel-ids',
                InputArgument::IS_ARRAY,
                'Specify sales channels to compile. Available ids can be seen with bin/console sales-channel:list',
                []
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $io->title('Start theme compilation');

        $salesChannelThemes = $this->themeProvider->load($context, $input->getOption('active-only'));

        foreach ($salesChannelThemes as $salesChannelId => $themeId) {
            $salesChannelIds = $input->getArgument('sales-channel-ids');

            if (0 < \count($salesChannelIds) && !\in_array($salesChannelId, $salesChannelIds)) {
                continue;
            }

            $salesChannelLabel = '<info>' . $salesChannelId . '</>';
            $salesChannelName = $this->getSalesChannelsByIds(array_keys($salesChannelThemes))[$salesChannelId]
                ?? ''
            ;

            if ('' !== $salesChannelName) {
                $salesChannelLabel = '<info>' . $salesChannelName . ' (' . $salesChannelLabel . ')</>';
            }

            $io->text(
                sprintf('Compiling theme for sales channel %s', $salesChannelLabel)
            );

            $start = microtime(true);

            $this->themeService->compileTheme(
                $salesChannelId,
                $themeId,
                $context,
                null,
                !$input->getOption('keep-assets')
            );
            $io->note(sprintf('Took %f seconds', microtime(true) - $start));
        }

        return Command::SUCCESS;
    }

    private function getSalesChannelsByIds(array $ids): array
    {
        $resultSet = $this->connection->executeQuery(
            <<<'SQL'
                SELECT LOWER(HEX(sales_channel_id)) as id, name
                FROM sales_channel_translation
                WHERE HEX(sales_channel_id) IN (?)
                GROUP BY id
                SQL,
            [
                $ids,
            ],
            [
                Connection::PARAM_STR_ARRAY,
            ],
        );

        return $resultSet->fetchAllKeyValue();
    }
}
