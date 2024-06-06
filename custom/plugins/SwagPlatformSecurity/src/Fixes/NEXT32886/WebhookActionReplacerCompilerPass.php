<?php

namespace Swag\Security\Fixes\NEXT32886;

use Doctrine\DBAL\Connection;
use Shopware\Commercial\FlowBuilder\WebhookFlowAction\Domain\Action\CallWebhookAction;
use Shopware\Core\Content\Media\File\FileUrlValidatorInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebhookActionReplacerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(CallWebhookAction::class)) {
            $definition = $container->getDefinition(CallWebhookAction::class);

            $definition->setClass(CallWebhookActionFix::class);

            $definition->addMethodCall('setLogger', [new Reference('logger')]);
            $definition->addMethodCall('setFileUrlValidator', [new Reference(FileUrlValidatorInterface::class)]);
            $definition->addMethodCall('setConnection', [new Reference(Connection::class)]);
        }
    }
}
