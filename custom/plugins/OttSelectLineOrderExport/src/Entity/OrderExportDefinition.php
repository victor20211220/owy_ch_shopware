<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderExportDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'order_export';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return OrderExportCollection::class;
    }

    public function getEntityClass(): string
    {
        return OrderExportEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('order_id', 'orderId'))->addFlags(new PrimaryKey(), new Required()),
            new BoolField('exported', 'exported'),
        ]);
    }
}
