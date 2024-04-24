<?php declare(strict_types=1);

namespace Swag\Security\Components;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('services-settings')]
abstract class AbstractSecurityFix extends Struct implements EventSubscriberInterface
{
    abstract public static function getTicket(): string;

    abstract public static function getMinVersion(): string;

    public static function isValidForVersion(string $version): bool
    {
        if (version_compare(static::getMinVersion(), $version, '>')) {
            return false;
        }

        if (static::getMaxVersion() && version_compare(static::getMaxVersion(), $version, '<')) {
            return false;
        }

        return true;
    }

    public static function getMaxVersion(): ?string
    {
        return null;
    }

    public static function getSubscribedEvents(): array
    {
        return [];
    }

    /**
     * Executed on any plugin boot
     */
    public static function boot(ContainerInterface $container): void
    {
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
    }
}
