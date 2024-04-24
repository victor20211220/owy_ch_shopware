<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Core\Content\SearchLog;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SearchLogEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $query = null;
    protected ?int $hits = null;
    protected ?int $origin = null;

    protected ?string $salesChannelId = null;
    protected ?SalesChannelEntity $salesChannel = null;

    protected ?string $languageId = null;
    protected ?LanguageEntity $language = null;

    protected ?array $additionalHits = null;

    public function getQuery(): ?string { return $this->query; }
    public function setQuery(string $value): void { $this->query = $value; }

    public function getHits(): ?int { return $this->hits; }
    public function setHits(int $value): void { $this->hits = $value; }

    public function getAdditionalHits(): ?array { return $this->additionalHits; }
    public function setAdditionalHits(array $value): void { $this->additionalHits = $value; }

    public function getOrigin(): ?int { return $this->origin; }
    public function setOrigin(int $value): void { $this->origin = $value; }

    public function getSalesChannel(): ?SalesChannelEntity { return $this->salesChannel; }
    public function setSalesChannel(SalesChannelEntity $value): void { $this->salesChannel = $value; }

    public function getSalesChannelId(): ?string { return $this->salesChannelId; }
    public function setSalesChannelId(string $value): void { $this->salesChannelId = $value; }

    public function getLanguage(): ?LanguageEntity { return $this->language; }
    public function setLanguage(LanguageEntity $value): void { $this->language = $value; }

    public function getLanguageId(): ?string { return $this->languageId; }
    public function setLanguageId(string $value): void { $this->languageId = $value; }
}
