<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Bootstrap;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;

class DefaultConfig
{
    const MODUL_NAME = 'CbaxModulLexicon';
    const DEFAULT_BASEPATH = "Lexikon";
    const DEFAULT_BASEPATHCONTENT = "Inhaltsverzeichnis";

    const BASE_PATH_SNIPPET = 'cbaxLexicon.basisPathSnippet';
    const BASE_PATH_CONTENTS_SNIPPET = 'cbaxLexicon.basisPathContentsSnippet';
    /*
     * Standardwerte
     */
    private $defaults = array(
        'activeProperties'    => array('name', 'value')
    );
    private $defaultCmsPageFields = array(
        'cmsPageIndex' => ['Coolbax Lexicon Overview', 'Coolbax Lexikon Übersicht'],
        'cmsPageListing' => ['Coolbax Lexicon Listing', 'Coolbax Lexikon Listing'],
        'cmsPageContent' => ['Coolbax Lexicon Content', 'Coolbax Lexikon Inhalt'],
        'cmsPageDetail' => ['Coolbax Lexicon Detail', 'Coolbax Lexikon Detail']);

    /**
     * beim aktivieren des Plugins die Standardwerte setzen
     */
    public function activate(array $services, Context $context): void
    {
        $this->checkSnippets($services);

        $systemConfigService = $services['systemConfigService'];

        $shopwareConfig = $systemConfigService->get(self::MODUL_NAME . '.config') ?? [];

        // Standardkonfiguration setzen, wenn noch nichts eingetragen wurde
        $configs = $this->checkDefault($shopwareConfig, $this->defaults);
        // speichern der Konfig
        foreach ($configs as $key => $config) {
            $systemConfigService->set(self::MODUL_NAME . '.config.' . $key, $config);
        }

        foreach ($this->defaultCmsPageFields as $key => $value) {
            if (empty($shopwareConfig[$key])) {
                $pageId = $this->getCmsPageIdByName($value, $services, $context);
                if (!empty($pageId)) {
                    $systemConfigService->set(self::MODUL_NAME . '.config.' . $key, $pageId);
                }
            }
        }
    }

    /**
     * Standardkonfiguration setzen, wenn noch nichts eingetragen wurde
     * @param array $configs aktuelle Konfig
     * @param array $configValues Standardkonfig
     * @return array
     */
    public function checkDefault(array $configs, array $configValues): array
    {
        $config = array();

        // setzen der Standardwerte, wenn in die Felder noch nicht eingetragen wurde
        foreach ($configValues as $key => $value) {
            if (!isset($configs[$key])) {
                $config[$key] = $configValues[$key];
            }
        }

        return $config;
    }

    public function checkSnippets(array $services): void
    {
        /** @var SystemConfigService $configService */
        $configService = $services['systemConfigService'];

        // get old config keys, null if they don't exist
        $oldBasisPath = $configService->get(self::MODUL_NAME.'.config.basisPath');
        $oldBasisPathContent = $configService->get(self::MODUL_NAME.'.config.basisPathContents');

        // add default snippet values if $basisPath or $basisPathContent is null else add their value to the snippet
        $snippets = [
            self::BASE_PATH_SNIPPET => $oldBasisPath ?? self::DEFAULT_BASEPATH,
            self::BASE_PATH_CONTENTS_SNIPPET => $oldBasisPathContent ?? self::DEFAULT_BASEPATHCONTENT
        ];

        $this->addDefaultSnippet($services, $snippets);

        // delete old keys if they exist
        if (!empty($oldBasisPath)) {
            $configService->set(self::MODUL_NAME.'.config.basisPath', null);
        }

        if (!empty($oldBasisPathContent)) {
            $configService->set(self::MODUL_NAME.'.config.basisPathContents', null);
        }
    }

    public function snippetExists(string $snippetName, string $snippetSetId, Connection $connection): bool
    {
        $query = "SELECT id FROM snippet WHERE translation_key LIKE :snippetName AND snippet_set_id LIKE :snippetSetId";
        $result = $connection->fetchAllAssociative($query, ['snippetName' => $snippetName, 'snippetSetId' => $snippetSetId]);

        return !empty($result);
    }

    public function addDefaultSnippet(array $services, array $snippets): void
    {
        /** @var Connection $connection  */
        $connection = $services['connectionService'];

        $rows = $connection->fetchAllAssociative("SELECT id FROM snippet_set");

        foreach ($rows as $row) {

            foreach ($snippets as $snippetName => $defaultValue) {
                if (!$this->snippetExists($snippetName, $row['id'], $connection)) {
                    $query = "INSERT IGNORE INTO `snippet` (`id`, `translation_key`, `value`, `author`, `snippet_set_id`, `created_at`)
                                VALUES (:id, :snippetName, :defaultValue, 'System', :snippetSetId, :createdAt);";
                    $result = $connection->executeStatement($query, [
                        'id' => Uuid::randomBytes(),
                        'snippetName' => $snippetName,
                        'defaultValue' => $defaultValue,
                        'snippetSetId' => $row['id'],
                        'createdAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
                    ]);
                }
            }
        }
    }

    private function getCmsPageIdByName(array $names, array $services, Context $context): ?string
    {
        $cmsPageRepository = $services['cmsPageRepository'];

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', 'cbax_lexicon'));
        $criteria->addFilter(new EqualsAnyFilter('name', $names));
        $criteria->addFilter(new EqualsFilter('locked', true));
        $pageId = $cmsPageRepository->searchIds($criteria, $context)->firstId();

        return $pageId;
    }
}
