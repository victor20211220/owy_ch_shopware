<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\ShoppingWorlds\Cms;

use Shopware\Core\Framework\Struct\Struct;
use Cbax\ModulLexicon\Core\Content\Bundle\LexiconEntryEntity;

class LexiconStruct extends Struct
{
    /**
     * @var string|null
     */
    protected $entryId;

    /**
     * @var LexiconEntryEntity|null
     */
    protected $entry;

    /**
     * @var array|null
     */
    protected $salesChannelProducts;

    /**
     * @var array|null
     */
    protected $entries;

    /**
     * @var string|null
     */
    protected $char;

    /**
     * @return string|null
     */
    public function getEntryId(): ?string
    {
        return $this->entryId;
    }

    /**
     * @param string|null $entryId
     */
    public function setEntryId(?string $entryId): void
    {
        $this->entryId = $entryId;
    }

    /**
     * @return LexiconEntryEntity|null
     */
    public function getEntry(): ?LexiconEntryEntity
    {
        return $this->entry;
    }

    /**
     * @param LexiconEntryEntity|null $entry
     */
    public function setEntry(?LexiconEntryEntity $entry): void
    {
        $this->entry = $entry;
    }

    /**
     * @return array|null
     */
    public function getSalesChannelProducts(): ?array
    {
        return $this->salesChannelProducts;
    }

    /**
     * @param array|null $salesChannelProducts
     */
    public function setSalesChannelProducts(?array $salesChannelProducts): void
    {
        $this->salesChannelProducts = $salesChannelProducts;
    }

    public function getApiAlias(): string
    {
        return 'cms_lexicon_struct';
    }

    /**
     * @return array|null
     */
    public function getEntries(): ?array
    {
        return $this->entries;
    }

    /**
     * @param array|null $entries
     */
    public function setEntries(?array $entries): void
    {
        $this->entries = $entries;
    }

    /**
     * @return string|null
     */
    public function getChar(): ?string
    {
        return $this->char;
    }

    /**
     * @param string|null $char
     */
    public function setChar(?string $char): void
    {
        $this->char = $char;
    }
}

