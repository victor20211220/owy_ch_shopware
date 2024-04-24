<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Components;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Uninstaller
{
    private const PLUGIN_PREFIX = 'NetzpSearchAdvanced6.';

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeTables();
        $this->removeConfiguration($uninstallContext);
    }

    private function removeTables()
    {
        $connection = $this->container->get(Connection::class);
        try {
            $connection->executeUpdate('DROP TABLE IF EXISTS `s_plugin_netzp_search_log`');
            $connection->executeUpdate('DROP TABLE IF EXISTS `s_plugin_netzp_search_synonyms`');
        }
        catch (\Exception) {
            //
        }
    }

    public function removeConfiguration(UninstallContext $uninstallContext)
    {
        $context = $uninstallContext->getContext();
        $repoConfig = $this->container->get('system_config.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('configurationKey', self::PLUGIN_PREFIX));
        $idSearchResult = $repoConfig->searchIds($criteria, $context);

        $ids = \array_map(static fn($id) => ['id' => $id], $idSearchResult->getIds());

        $repoConfig->delete($ids, $context);
    }
}
