<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Command;

use Ott\Base\Command\SingletonCommand;
use Ott\SelectlineImport\Gateway\ImportExtensionGateway;
use Ott\SelectlineImport\ImportTypeProcessor\ImportTypeProcessorFactory;
use Ott\SelectlineImport\Service\ImportMessageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportMessageCommand extends SingletonCommand
{
    protected ImportExtensionGateway $importExtensionGateway;
    private ImportTypeProcessorFactory $processorFactory;
    private ImportMessageManager $importMessageManager;

    public function __construct(
        ImportTypeProcessorFactory $processorFactory,
        ImportMessageManager $importMessageManager,
        ImportExtensionGateway $importExtensionGateway
    )
    {
        parent::__construct();
        $this->processorFactory = $processorFactory;
        $this->importMessageManager = $importMessageManager;
        $this->importExtensionGateway = $importExtensionGateway;
    }

    public function configure(): void
    {
        $this->setName('ott:import:message');
        $this->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Count of messages to import');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $messageCount = 0 === (int) $input->getOption('limit') ? 10 : (int) $input->getOption('limit');

        if ($this->hasLockFile()) {
            $output->writeln('Command already running');

            return Command::SUCCESS;
        }

        $this->lockProcess();

        $progress = new ProgressBar($output);
        $messages = $this->importMessageManager->get($messageCount);
        $progress->start($messageCount);
        $start = microtime(true);
        foreach ($messages as $message) {
            $processor = $this->processorFactory->getProcessor($message->getType());
            $processor->import($message);
            $this->importMessageManager->delete($message->getId());
            $progress->advance();
        }

        $progress->finish();
        $output->writeln('');

        return Command::SUCCESS;
    }
}
