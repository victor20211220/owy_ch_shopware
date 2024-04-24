<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Command;

use Ott\Base\Command\SingletonCommand;
use Ott\SelectlineImport\Gateway\ImportExtensionGateway;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteProductsCommand extends SingletonCommand
{
    protected ImportExtensionGateway $importExtensionGateway;

    public function __construct(
        ImportExtensionGateway $importExtensionGateway
    )
    {
        parent::__construct();
        $this->importExtensionGateway = $importExtensionGateway;
    }

    public function configure(): void
    {
        $this->setName('ott:delete:products');
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_REQUIRED,
            'set limit for max delete products',
            500
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getOption('limit');

        if ($limit > $this->importExtensionGateway->countDeleteProducts()) {
            $this->importExtensionGateway->deleteProducts();
        }

        return Command::SUCCESS;
    }
}
