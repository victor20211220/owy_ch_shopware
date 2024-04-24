<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Core\Content\SearchSynonym;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class SearchSynonymEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $synonym = null;
    protected ?string $replace = null;

    public function getSynonym(): ?string { return $this->synonym; }
    public function setSynonym(string $value): void { $this->synonym = $value; }

    public function getReplace(): ?string { return $this->replace; }
    public function setReplace(string $value): void { $this->replace = $value; }
}
