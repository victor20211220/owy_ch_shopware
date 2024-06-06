<?php

namespace Swag\Security\Fixes\NEXT32886;

use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NEXT32886Fix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-32886';
    }

    public static function getMinVersion(): string
    {
        return '6.5.0.0';
    }

    public static function getMaxVersion(): string
    {
        return '6.5.7.4';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new WebhookActionReplacerCompilerPass());
    }
}
