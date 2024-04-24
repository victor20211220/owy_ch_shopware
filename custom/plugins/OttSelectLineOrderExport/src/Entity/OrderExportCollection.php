<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class OrderExportCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderExportEntity::class;
    }
}
