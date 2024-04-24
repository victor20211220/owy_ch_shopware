<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Dbal\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ImportPictureMessageEntity extends Entity
{
    use EntityIdTrait;
    public const TABLE = 'import_picture_message';
    private array $workload;
    private string $productId;

    public function getWorkload(): array
    {
        return $this->workload;
    }

    public function setWorkload(array $workload): self
    {
        $this->workload = $workload;

        return $this;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): self
    {
        $this->productId = $productId;

        return $this;
    }
}
