<?php declare(strict_types=1);

namespace Ott\SelectlineImport\ImportTypeProcessor;

use Ott\Base\Import\ImportService;
use Ott\Base\Import\Module\CustomerModule;
use Ott\Base\Import\Module\ProductModule;
use Ott\Base\Service\MediaHelper;
use Ott\SelectlineImport\Gateway\ImportExtensionGateway;
use Ott\SelectlineImport\Service\ImportPictureMessageManager;
use Psr\Log\LoggerInterface;

abstract class ImportProcessor
{
    protected MediaHelper $mediaService;
    protected ImportService $importService;
    protected ProductModule $productModule;
    protected LoggerInterface $logger;
    protected ImportExtensionGateway $importExtensionGateway;
    protected CustomerModule $customerModule;
    protected ImportPictureMessageManager $importPictureMessageManager;

    public function __construct(
        ImportService $importService,
        ProductModule $productModule,
        MediaHelper $mediaService,
        ImportExtensionGateway $importExtensionGateway,
        CustomerModule $customerModule,
        LoggerInterface $logger,
        ImportPictureMessageManager $importPictureMessageManager
    )
    {
        $this->mediaService = $mediaService;
        $this->importService = $importService;
        $this->productModule = $productModule;
        $this->logger = $logger;
        $this->importExtensionGateway = $importExtensionGateway;
        $this->customerModule = $customerModule;
        $this->importPictureMessageManager = $importPictureMessageManager;
    }

    public function getType(): string
    {
        return static::PROCESSOR_TYPE;
    }
}
