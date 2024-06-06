<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT32889;

use Shopware\Core\System\StateMachine\Api\StateMachineActionController;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class NEXT32889Fix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-32889';
    }

    public static function getMinVersion(): string
    {
        return '6.3.0.0';
    }

    public static function getMaxVersion(): string
    {
        return '6.5.7.4';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(StateMachineActionController::class);
        $definition->setClass(PatchedStateMachineActionController::class);
    }
}
