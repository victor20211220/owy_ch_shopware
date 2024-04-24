<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Components;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class RegisterSearchProviderEvent extends Event
{
    final public const EVENT_NAME = 'netzp.search.register';

    protected $context;
    protected $data;

    public function __construct(SalesChannelContext $context)
    {
        $this->context = $context;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
