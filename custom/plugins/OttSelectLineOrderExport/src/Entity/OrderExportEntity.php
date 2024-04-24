<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class OrderExportEntity extends Entity
{
    public const TABLE = 'order_export';
    protected string $orderId;
    private bool $exported;

    public function isExported(): bool
    {
        return $this->exported;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
        $this->_uniqueIdentifier = $orderId;
    }

    public function setExported(bool $exported): self
    {
        $this->exported = $exported;

        return $this;
    }
}
