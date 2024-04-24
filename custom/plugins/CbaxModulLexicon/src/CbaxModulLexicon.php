<?php declare(strict_types=1);

namespace Cbax\ModulLexicon;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

use Cbax\ModulLexicon\Bootstrap\DefaultConfig;
use Cbax\ModulLexicon\Bootstrap\Database;
use Cbax\ModulLexicon\Bootstrap\Updater;
use Cbax\ModulLexicon\Bootstrap\CmsPageCreator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class CbaxModulLexicon extends Plugin
{
    public function install(InstallContext $installContext): void
    {
		parent::install($installContext);
    }
	
	public function postInstall(InstallContext $installContext): void
    {
        $services = $this->getServices();
        $db = new Database();
        $db->updateDatabaseTables($services);
    }

    public function update(UpdateContext $updateContext): void
    {
        $services = $this->getServices();
        $db = new Database();
        $db->updateDatabaseTables($services);

        $update = new Updater();
        $update->updateData($this->container, $updateContext);

        $cmsPageCreator = new CmsPageCreator();
        $cmsPageCreator->createDefaultLexiconCmsPages($services, $updateContext->getContext());
        $cmsPageCreator->updateLexiconCmsPages($services, $updateContext->getContext());

        $builder = new DefaultConfig();
        $builder->activate($services, $updateContext->getContext());

        parent::update($updateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
		parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

		// Datenbank Tabellen löschen (erst wenn keepUserData funktioniert)
        $services = $this->getServices();
        $db = new Database();
        $db->removeDatabaseTables($services);

        $cmsPageCreator = new CmsPageCreator();
        // CMS Slots löschen
        $cmsPageCreator->deleteLexiconCmsSlots($services, $uninstallContext->getContext());

        // CMS Blocks löschen
        $cmsPageCreator->deleteLexiconCmsBlocks($services, $uninstallContext->getContext());

        // CMS Pages löschen
        $cmsPageCreator->deleteLexiconCmsPages($services, $uninstallContext->getContext());
    }

    public function activate(ActivateContext $activateContext): void
    {
        $services = $this->getServices();

        $cmsPageCreator = new CmsPageCreator();
        $cmsPageCreator->createDefaultLexiconCmsPages($services, $activateContext->getContext());

        $builder = new DefaultConfig();
        $builder->activate($services, $activateContext->getContext());

        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }

    public function postUpdate(UpdateContext $updateContext) : void {
        $services = $this->getServices();

        $updater = new Updater();
        $updater->afterUpdateSteps($services, $updateContext);
    }

    /**
     * Load services.xml to add cms data resolver services
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator($this->getPath() . '/Core/Content/DependencyInjection'));
        $loader->load('services.xml');
    }

    private function getServices() {
        $services = array();

        /* Standard Services */
        $services['systemConfigService'] = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');
        $services['connectionService'] =  $this->container->get(Connection::class);
        $services['languageRepository'] = $this->container->get('language.repository');
        $services['cmsPageRepository'] = $this->container->get('cms_page.repository');
        $services['cmsBlockRepository'] = $this->container->get('cms_block.repository');
        $services['cmsSlotRepository'] = $this->container->get('cms_slot.repository');
        $services['cmsSlotTranslationRepository'] = $this->container->get('cms_slot_translation.repository');

        return $services;
    }
}
