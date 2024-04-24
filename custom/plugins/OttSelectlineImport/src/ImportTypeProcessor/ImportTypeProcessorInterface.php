<?php declare(strict_types=1);

namespace Ott\SelectlineImport\ImportTypeProcessor;

use Ott\SelectlineImport\Dbal\Entity\ImportMessageEntity;

interface ImportTypeProcessorInterface
{
    public function import(ImportMessageEntity $message): void;

    public function getType(): string;
}
