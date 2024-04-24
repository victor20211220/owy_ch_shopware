<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Core;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Struct\Struct;

class SearchResult extends Struct
{
    protected int $type;
    protected string $id;
    protected string $title = '';
    protected string $description = '';
    protected array $breadcrumb = [];
    protected ?MediaEntity $media = null;
    protected int $total = 0;

    public function getType(): int  { return $this->type; }
    public function setType(int $value): void { $this->type = $value; }

    public function getId(): string { return $this->id; }
    public function setId(string $value): void { $this->id = $value; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $value): void { $this->title = $value; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $value): void { $this->description = $value; }

    public function getBreadcrumb(): array { return $this->breadcrumb; }
    public function setBreadcrumb(array $value): void { $this->breadcrumb = $value; }

    public function getMedia(): ?MediaEntity { return $this->media; }
    public function setMedia(?MediaEntity $value): void { $this->media = $value; }

    public function getTotal(): int { return $this->total; }
    public function setTotal(int $value): void { $this->total = $value; }
}
