<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Command;

use Ott\SelectLineOrderExport\Service\OrderExportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderExportCommand extends Command
{
    private OrderExportService $orderExportService;
    private LoggerInterface $logger;

    public function __construct(OrderExportService $orderExportService, LoggerInterface $logger, $name = null)
    {
        parent::__construct($name);

        $this->orderExportService = $orderExportService;
        $this->logger = $logger;
    }

    public function configure(): void
    {
        $this->setName('ott:order:export');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->orderExportService->getOrders();
        $progress = new ProgressBar($output);
        $progress->start(\count($orders));

        foreach ($orders as $order) {
            try {
                $this->orderExportService->processOrder($order);
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf(
                        '%s: %s',
                        'Could not parse order ' . $order['order_number'],
                        $e->getMessage()
                    ),
                    [
                        $e->getFile(),
                        $e->getLine(),
                    ]
                );
            }
            $progress->advance();
        }

        $progress->finish();
        $output->writeln('');

        return Command::SUCCESS;
    }
}
