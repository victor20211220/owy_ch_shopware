<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Content\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class LexiconSalesChannelEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $cbaxLexiconEntryId;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var SalesChannelEntity|null
     */
    protected $salesChannel;

    /**
     * @var LexiconEntryEntity|null
     */
    protected $lexiconEntry;
	
	public function getCbaxLexiconEntryId(): ?string
    {
        return $this->cbaxLexiconEntryId;
    }

    public function setCbaxLexiconEntryId(string $cbaxLexiconEntryId): void
    {
        $this->cbaxLexiconEntryId = $cbaxLexiconEntryId;
    }
	
    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return SalesChannelEntity|null
     */
    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    /**
     * @param SalesChannelEntity|null $salesChannel
     */
    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    /**
     * @return LexiconEntryEntity|null
     */
    public function getLexiconEntry(): ?LexiconEntryEntity
    {
        return $this->lexiconEntry;
    }

    /**
     * @param LexiconEntryEntity|null $lexiconEntry
     */
    public function setLexiconEntry(?LexiconEntryEntity $lexiconEntry): void
    {
        $this->lexiconEntry = $lexiconEntry;
    }




}

