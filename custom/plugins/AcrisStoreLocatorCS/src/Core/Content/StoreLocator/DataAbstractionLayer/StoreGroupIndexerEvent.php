<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\StoreLocator\DataAbstractionLayer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;

class StoreGroupIndexerEvent extends NestedEvent
{
    private Context $context;
    private array $ids;

    public function __construct(array $ids, Context $context)
    {
        $this->context = $context;
        $this->ids = $ids;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getIds(): array
    {
        return $this->ids;
    }
}
