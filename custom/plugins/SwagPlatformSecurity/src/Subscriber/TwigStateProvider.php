<?php declare(strict_types=1);

namespace Swag\Security\Subscriber;

use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Swag\Security\Components\State;

#[Package('services-settings')]
class TwigStateProvider
{
    public function __construct(private readonly State $state)
    {
    }

    public function __invoke(StorefrontRenderEvent $event): void
    {
        $event->setParameter('swagSecurity', $this->state);
    }
}
