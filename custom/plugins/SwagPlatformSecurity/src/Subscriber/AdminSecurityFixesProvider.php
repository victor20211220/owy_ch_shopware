<?php declare(strict_types=1);

namespace Swag\Security\Subscriber;

use Shopware\Core\Framework\Log\Package;
use Swag\Security\Components\State;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[Package('services-settings')]
class AdminSecurityFixesProvider
{
    public function __construct(private readonly State $state)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');

        if ($route !== 'api.info.config') {
            return;
        }

        $context = json_decode((string) $event->getResponse()->getContent(), true);
        $context['swagSecurity'] = array_map(function ($state) {
            return $state::getTicket();
        }, $this->state->getActiveFixes());

        $event->setResponse(new JsonResponse($context));
    }
}
