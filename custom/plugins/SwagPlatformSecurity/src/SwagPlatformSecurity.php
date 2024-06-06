<?php declare(strict_types=1);

namespace Swag\Security;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Kernel;
use Swag\Security\Components\AbstractSecurityFix;
use Swag\Security\Components\RemoveDisabledServicesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Package('services-settings')]
class SwagPlatformSecurity extends Plugin
{
    final public const PLUGIN_NAME = 'SwagPlatformSecurity';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->fetchPluginConfig($container);
        $container->addCompilerPass(new RemoveDisabledServicesCompilerPass());
    }

    public function boot(): void
    {
        parent::boot();

        \assert($this->container instanceof ContainerInterface, 'Container is not set yet, please call setContainer() before calling boot(), see `platform/Core/Kernel.php:186`.');

        foreach ((array) $this->container->getParameter('SwagPlatformSecurity.activeFixes') as $securityFix) {
            $securityFix::boot($this->container);
        }
    }

    private function fetchPluginConfig(ContainerBuilder $container): void
    {
        try {
            $qb = Kernel::getConnection();

            $config = $qb->fetchAllKeyValue('SELECT ticket, active FROM swag_security_config');
        } catch (\Throwable $e) {
            $config = [];
        }

        foreach ($config as &$item) {
            $item = (bool) $item;
        }
        unset($item);

        $shopwareVersion = $_SERVER['SHOPWARE_FAKE_VERSION'] ?? $container->getParameter('kernel.shopware_version');
        /** @var class-string<AbstractSecurityFix>[] $knownIssues */
        $knownIssues = $container->getParameter('SwagPlatformSecurity.knownIssues');
        $availableFixes = [];
        $activeFixes = [];

        foreach ($knownIssues as $knownIssue) {
            if (!$knownIssue::isValidForVersion($shopwareVersion)) {
                continue;
            }

            $availableFixes[] = $knownIssue;

            if (\array_key_exists($knownIssue::getTicket(), $config) && !$config[$knownIssue::getTicket()]) {
                continue;
            }

            $knownIssue::buildContainer($container);

            $activeFixes[] = $knownIssue;
        }

        $container->setParameter('SwagPlatformSecurity.activeFixes', $activeFixes);
        $container->setParameter('SwagPlatformSecurity.availableFixes', $availableFixes);
    }
}
