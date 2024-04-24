<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Dbal\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ImportPictureMessageCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ImportPictureMessageEntity::class;
    }
}
