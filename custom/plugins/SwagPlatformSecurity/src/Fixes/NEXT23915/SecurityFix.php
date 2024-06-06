<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23915;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Version\Aggregate\VersionCommit\VersionCommitDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Version\VersionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\VersionManager;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-23915';
    }

    public static function getMinVersion(): string
    {
        return '6.5.0.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.5.6.0';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $decorated = new Definition(PatchedVersionManager::class);
        $decorated->setArguments([
            new Reference('.inner'),
            new Reference(EntitySearcherInterface::class),
            new Reference(VersionCommitDefinition::class),
            new Reference(VersionDefinition::class),
        ]);

        $decorated->setDecoratedService(VersionManager::class);
        $container->setDefinition(PatchedVersionManager::class, $decorated);
    }
}
