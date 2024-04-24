<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Dbal\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ImportMessageCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ImportMessageEntity::class;
    }
}
