<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Dbal\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ImportMessageEntity extends Entity
{
    use EntityIdTrait;
    public const TABLE = 'import_message';
    private array $workload;
    private string $file;
    private string $type;

    public function getWorkload(): array
    {
        return $this->workload;
    }

    public function setWorkload(array $workload): self
    {
        $this->workload = $workload;

        return $this;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
