<?php declare(strict_types=1);

namespace Swag\Security\Components;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Package('services-settings')]
class RemoveDisabledServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $services = $container->findTaggedServiceIds('swag.security.fix');
        $activeFixes = (array) $container->getParameter('SwagPlatformSecurity.activeFixes');

        /**
         * @var class-string<AbstractSecurityFix> $id
         */
        foreach ($services as $id => $tag) {
            $ticket = $id::getTicket();

            if (!\in_array($ticket, $activeFixes, true)) {
                $container->removeDefinition($id);
            }
        }
    }
}
