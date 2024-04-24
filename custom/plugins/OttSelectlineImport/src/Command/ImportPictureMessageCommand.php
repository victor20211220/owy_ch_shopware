<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Command;

use Composer\Console\Input\InputArgument;
use Ott\Base\Command\SingletonCommand;
use Ott\SelectlineImport\Gateway\ImportExtensionGateway;
use Ott\SelectlineImport\ImportTypeProcessor\PictureImportProcessor;
use Ott\SelectlineImport\Service\ImportPictureMessageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportPictureMessageCommand extends SingletonCommand
{
    protected ImportExtensionGateway $importExtensionGateway;
    private PictureImportProcessor $pictureImportProcessor;
    private ImportPictureMessageManager $importPictureMessageManager;

    public function __construct(
        PictureImportProcessor $pictureImportProcessor,
        ImportPictureMessageManager $importPictureMessageManager,
        ImportExtensionGateway $importExtensionGateway
    )
    {
        parent::__construct();
        $this->importExtensionGateway = $importExtensionGateway;
        $this->pictureImportProcessor = $pictureImportProcessor;
        $this->importPictureMessageManager = $importPictureMessageManager;
    }

    public function configure(): void
    {
        $this->setName('ott:import:picture:message');
        $this->addArgument('counter', InputArgument::OPTIONAL, 'worker counter', 1);
        $this->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Count of messages to import');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $messageCount = 0 === (int) $input->getOption('limit') ? 10 : (int) $input->getOption('limit');
        $counter = (int) $input->getArgument('counter');

        if ($this->hasLockFile($counter)) {
            $output->writeln('Command already running');

            return Command::SUCCESS;
        }

        $this->lockProcess($counter);

        $progress = new ProgressBar($output);
        $messages = $this->importPictureMessageManager->get($messageCount);
        $progress->start($messageCount);
        foreach ($messages as $message) {
            $this->pictureImportProcessor->import($message);
            $this->importPictureMessageManager->delete($message->getId());
            $progress->advance();
        }

        $progress->finish();
        $output->writeln('');

        return Command::SUCCESS;
    }
}
