<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6;

use NetzpSearchAdvanced6\Components\Installer;
use NetzpSearchAdvanced6\Components\Uninstaller;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class NetzpSearchAdvanced6 extends Plugin
{
    public function install(InstallContext $context): void
    {
        parent::install($context);
        (new Installer($this->container))->install();
    }

    public function activate(ActivateContext $context): void
    {
        parent::install($context);
        (new Installer($this->container))->activate();
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);
        $this->removeMigrations();
        (new Uninstaller($this->container))->uninstall($context);
    }

    public function postInstall(InstallContext $installContext): void
    {
        // sometimes the template inheritance does not take effect correctly
        // here the installation date is updated, so it should work
        $repo = $this->container->get('plugin.repository');
        $plugin = $repo->search(
            (new Criteria())->addFilter(new EqualsFilter('name', 'NetzpSearchAdvanced6')),
            new Context(new SystemSource())
        )->first();

        if($plugin) {
            $repo->update([
                [
                    'id'          => $plugin->getId(),
                    'installedAt' => (new \DateTime())->format('Y-m-d H:i:s.v')
                ]
            ], new Context(new SystemSource()));
        }
    }
}
