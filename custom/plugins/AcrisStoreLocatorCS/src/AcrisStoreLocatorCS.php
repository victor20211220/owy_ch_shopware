<?php declare(strict_types=1);

namespace Acris\StoreLocator;

use Acris\ImportExport\AcrisImportExport;
use Acris\ImportExport\AcrisImportExportCS;
use Acris\StoreLocator\Custom\StoreGroupDefinition;
use Acris\StoreLocator\Custom\StoreLocatorDefinition;
use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Acris\StoreLocator\Storefront\Framework\Seo\SeoUrlRoute\StoreGroupPageSeoUrlRoute;
use Acris\StoreLocator\Storefront\Framework\Seo\SeoUrlRoute\StorePageSeoUrlRoute;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\ImportExport\ImportExportProfileEntity;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderEntity;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;

class AcrisStoreLocatorCS extends Plugin
{
    const IMPORT_EXPORT_PROFILE_NAME = 'ACRIS Store Locator';
    const PROCESS_TYPE_SYNC_API = 'sync_api';
    const DEFAULT_SYNC_API_STORE_IMPORT_NAME_V01 = "ACRIS-Store-Locator-Sync-API-V01";
    const DEFAULT_STORE_LAYOUT_CMS_PAGE_NAME = 'Default store page Layout';
    public const DEFAUL_MEDIA_FOLDER_NAME = "Store Locator Media";
    public const DEFAULT_MEDIA_FOLDER_CUSTOM_FIELD = 'acrisStoreLocatorDefaultFolder';
    public const DEFAULT_MEDIA_FOLDER_ASSOCIATION_FIELDS = ['storeMedia'];


    public function update(UpdateContext $updateContext): void
    {
        if(version_compare($updateContext->getCurrentPluginVersion(), '1.1.3', '<')) {
            $this->addImportExportProfile($updateContext->getContext());
        }

        if(version_compare($updateContext->getCurrentPluginVersion(), '1.3.0', '<')
            && version_compare($updateContext->getUpdatePluginVersion(), '1.3.0', '>=')) {
            $this->addImportExportProfile($updateContext->getContext());
        }

        if(version_compare($updateContext->getCurrentPluginVersion(), '1.3.0', '<')
            && version_compare($updateContext->getUpdatePluginVersion(), '1.3.0', '>=')) {
            $this->addImportExportProfile($updateContext->getContext());
        }

        if(version_compare($updateContext->getCurrentPluginVersion(), '3.1.0', '<')
            && version_compare($updateContext->getUpdatePluginVersion(), '3.1.0', '>=')) {
            $this->createDefaultMediaUploadFolder($updateContext->getContext());
        }

        if(version_compare($updateContext->getCurrentPluginVersion(), '3.3.0', '<')
            && version_compare($updateContext->getUpdatePluginVersion(), '3.3.0', '>=')) {
            $this->updateImportExportProfile($updateContext->getContext());
            $this->insertDefaultValuesForImportExportPlugin($updateContext->getContext());
        }
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        if(version_compare($updateContext->getCurrentPluginVersion(), '2.5.0', '<') && $updateContext->getPlugin()->isActive() === true) {
            $this->insertDefaultValues($updateContext->getContext());
            $this->updateGroupForStores($updateContext->getContext());
        }

        if(version_compare($updateContext->getCurrentPluginVersion(), '2.5.5', '<') && $updateContext->getPlugin()->isActive() === true) {
            $this->insertDefaultSeoUrlTemplate($updateContext->getContext());
            $this->updateImportExportProfile($updateContext->getContext());
        }

        if(version_compare($updateContext->getCurrentPluginVersion(), '2.6.0', '<') && $updateContext->getPlugin()->isActive() === true) {
            $this->addDefaultLayouts($updateContext->getContext());
        }

        if(version_compare($updateContext->getCurrentPluginVersion(), '2.7.5', '<') && $updateContext->getPlugin()->isActive() === true) {
            $this->cleanupImportExportProfile($updateContext->getContext());
            $this->optimizeImportExportProfile($updateContext->getContext());
        }
        if(version_compare($updateContext->getCurrentPluginVersion(), '3.3.1', '<')
            && version_compare($updateContext->getUpdatePluginVersion(), '3.3.1', '>=')
            && $updateContext->getPlugin()->isActive() === true) {
            $this->updateStateConversionField($updateContext->getContext());
        }
    }

    public function install(InstallContext $installContext): void
    {
        $this->addImportExportProfile($installContext->getContext());
        $this->createDefaultMediaUploadFolder($installContext->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        if ($context->keepUserData()) {
            return;
        }
        $this->cleanupImportExportProfile($context->getContext());
        $this->cleanupDatabase();
        $this->cleanDefaultLayout($context->getContext());
        $this->removeMediaUploadFolder($context->getContext());
        $this->removeDefaultValuesForImportExportPlugin($context->getContext());
    }

    public function activate(ActivateContext $context): void
    {
        parent::activate($context);
        $this->insertDefaultValues($context->getContext());
        $this->updateGroupForStores($context->getContext());
        $this->insertDefaultSeoUrlTemplate($context->getContext());
        $this->addDefaultLayouts($context->getContext());
        $this->insertDefaultValuesForImportExportPlugin($context->getContext());
    }

    private function addDefaultLayouts(Context $context): void
    {
        $cmsPageRepository = $this->container->get('cms_page.repository');

        $cmsPageName = self::DEFAULT_STORE_LAYOUT_CMS_PAGE_NAME;
        $existingSearchResult = $cmsPageRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $cmsPageName))->addFilter(new EqualsFilter('locked', true)), $context);
        if($existingSearchResult->getTotal() > 0) {
            return;
        }

        $cmsPage = new CmsPageEntity();
        $cmsPage->setId(Uuid::randomHex());
        $cmsPage->setName($cmsPageName);
        $cmsPage->setLocked(true);
        $cmsPage->setType('cms_stores');
        $cmsPage->setCustomFields(['acrisStoreLocatorDefaultAdded' => true]);

        $sectionPosition = 0;
        $sectionCollection = new CmsSectionCollection();
        $cmsPage->setSections($sectionCollection);
        list($blockFirst, $blockSecond) = $this->getBlockAndAddToSectionCollection($sectionCollection, $sectionPosition, $cmsPage->getId());
        $this->createSlotAndAdd($blockFirst);
        $this->createSlotAndAddSecond($blockSecond);

        $cmsPageData = $this->convertCmsPageEntity($cmsPage);
        $cmsPageRepository->upsert([$cmsPageData], $context);

        $connection = $this->container->get(Connection::class);
        $connection->executeStatement(
            'UPDATE cms_page SET locked = true WHERE id = (:id)',
            ['id' => Uuid::fromHexToBytes($cmsPage->getId())]
        );
    }

    private function getBlockAndAddToSectionCollection(CmsSectionCollection $sectionCollection, int $position, string $cmsPageId): array
    {
        $section = new CmsSectionEntity();
        $section->setId(Uuid::randomHex());
        $section->setPageId($cmsPageId);
        $section->setType('default');
        $section->setSizingMode('boxed');
        $section->setMobileBehavior('wrap');
        $section->setPosition($position);
        $sectionCollection->add($section);
        $blocksCollection = new CmsBlockCollection();
        $block = new CmsBlockEntity();
        $block->setId(Uuid::randomHex());
        $block->setSectionId($section->getId());
        $block->setType('text');
        $block->setPosition(1);
        $block->setSectionPosition('main');
        $block->setBackgroundMediaMode('cover');
        $block->setMarginTop('20px');
        $block->setMarginRight('20px');
        $block->setMarginBottom('20px');
        $block->setMarginLeft('20px');
        $blockSecond = new CmsBlockEntity();
        $blockSecond->setId(Uuid::randomHex());
        $blockSecond->setSectionId($section->getId());
        $blockSecond->setType('text');
        $blockSecond->setPosition(0);
        $blockSecond->setSectionPosition('main');
        $blockSecond->setBackgroundMediaMode('cover');
        $blockSecond->setMarginTop('20px');
        $blockSecond->setMarginRight('20px');
        $blockSecond->setMarginBottom('20px');
        $blockSecond->setMarginLeft('20px');
        $blocksCollection->add($block);
        $blocksCollection->add($blockSecond);
        $section->setBlocks($blocksCollection);
        $slotCollection = new CmsSlotCollection();
        $block->setSlots($slotCollection);
        $blockSecond->setSlots(new CmsSlotCollection());
        return [$block, $blockSecond];
    }

    private function createSlotAndAdd(CmsBlockEntity $block): void
    {
        $slot = new CmsSlotEntity();
        $slot->setId(Uuid::randomHex());
        $slot->setBlockId($block->getId());
        $slot->setType('acris-store-google-map');
        $slot->setSlot('content');
        $slot->setConfig([
            "store" => [
                "source" => "static",
                "value" => null
            ]
        ]);
        $slotCollection = $block->getSlots();
        $slotCollection->add($slot);
        $block->setSlots($slotCollection);
    }

    private function createSlotAndAddSecond(CmsBlockEntity $block): void
    {
        $slot = new CmsSlotEntity();
        $slot->setId(Uuid::randomHex());
        $slot->setBlockId($block->getId());
        $slot->setType('acris-store-details');
        $slot->setSlot('content');
        $slot->setConfig([
            "store" => [
                "source" => "static",
                "value" => null
            ]
        ]);
        $slotCollection = $block->getSlots();
        $slotCollection->add($slot);
        $block->setSlots($slotCollection);
    }

    private function convertCmsPageEntity(CmsPageEntity $cmsPageEntity): array
    {
        $sectionArray = [];
        foreach ($cmsPageEntity->getSections()->getElements() as $sectionEntity) {
            $blockArray = [];
            foreach ($sectionEntity->getBlocks()->getElements() as $blockEntity) {
                $slotArray = [];
                foreach ($blockEntity->getSlots()->getElements() as $slotEntity) {
                    $slotData = $slotEntity->getVars();
                    unset($slotData['block']);
                    $slotArray[] = $slotData;
                }
                $blockData = $blockEntity->getVars();
                $blockData['slots'] = $slotArray;
                unset($blockData['section']);
                unset($blockData['backgroundMedia']);
                $blockArray[] = $blockData;
            }
            $sectionData = $sectionEntity->getVars();
            $sectionData['blocks'] = $blockArray;
            unset($sectionData['page']);
            unset($sectionData['backgroundMedia']);
            $sectionArray[] = $sectionData;
        }
        $cmsPageData = $cmsPageEntity->getVars();
        $cmsPageData['sections'] = $sectionArray;
        unset($cmsPageData['previewMedia']);
        return $cmsPageData;
    }

    private function addImportExportProfile(Context $context): void
    {
        $importExportProfileRepository = $this->container->get('import_export_profile.repository');
        foreach ($this->getOptimizedSystemDefaultProfiles() as $profile) {
            $this->createIfNotExists($importExportProfileRepository, [['name' => 'name', 'value' => $profile['name']]], $profile, $context);
        }
    }

    private function insertDefaultSeoUrlTemplate(Context $context)
    {
        /** @var EntityRepository $seoUrlTemplateRepository */
        $seoUrlTemplateRepository = $this->container->get('seo_url_template.repository');
        $defaultSeoUrlTemplates = [
            [
                'routeName' => StorePageSeoUrlRoute::ROUTE_NAME,
                'entityName' => StoreLocatorDefinition::ENTITY_NAME,
                'template' => StorePageSeoUrlRoute::DEFAULT_TEMPLATE,
                'isValid' => true
            ],
            [
                'routeName' => StoreGroupPageSeoUrlRoute::ROUTE_NAME,
                'entityName' => StoreGroupDefinition::ENTITY_NAME,
                'template' => StoreGroupPageSeoUrlRoute::DEFAULT_TEMPLATE,
                'isValid' => true
            ]
        ];

        foreach ($defaultSeoUrlTemplates as $defaultSeoUrlTemplate) {
            $this->createSeoUrlTemplateIfNotExists($seoUrlTemplateRepository, $context, $defaultSeoUrlTemplate);
        }
    }

    /**
     * @param EntityRepository $entityRepository
     * @param Context $context
     * @param array $seoUrlTemplateData
     */
    private function createSeoUrlTemplateIfNotExists(EntityRepository $entityRepository, Context $context, array $seoUrlTemplateData): void
    {
        $exists = $entityRepository->search((new Criteria())->addFilter(new EqualsFilter('routeName', $seoUrlTemplateData['routeName']))->addFilter(new EqualsFilter('entityName', $seoUrlTemplateData['entityName']))->addFilter(new EqualsFilter('template', $seoUrlTemplateData['template'])), $context);
        if($exists->getTotal() === 0) {
            $entityRepository->create([$seoUrlTemplateData], $context);
        }
    }

    private function createIfNotExists(EntityRepository $repository, array $equalFields, array $data, Context $context)
    {
        $filters = [];
        foreach ($equalFields as $equalField) {
            $filters[] = new EqualsFilter($equalField['name'], $equalField['value']);
        }
        if(sizeof($filters) > 1) {
            $filter = new MultiFilter(MultiFilter::CONNECTION_OR, $filters);
        } else {
            $filter = array_shift($filters);
        }

        $searchResult = $repository->search((new Criteria())->addFilter($filter), $context);
        if($searchResult->count() == 0) {
            $repository->create([$data], $context);
        }
    }

    private function cleanupImportExportProfile(Context $context): void
    {
        $importExportProfile = $this->container->get('import_export_profile.repository');
        $storeLocatorProfiles = $importExportProfile->search((new Criteria())->addFilter(new EqualsFilter('sourceEntity', 'acris_store_locator')), $context);
        $ids = [];

        if ($storeLocatorProfiles->getTotal() > 0 && $storeLocatorProfiles->first()) {
            /** @var ImportExportProfileEntity $entity */
            foreach ($storeLocatorProfiles->getEntities() as $entity) {
                if ($entity->getSystemDefault() === true) {
                    $importExportProfile->update([
                        ['id' => $entity->getId(), 'systemDefault' => false ]
                    ], $context);
                }
                $ids[] = ['id' => $entity->getId()];
            }
            $importExportProfile->delete($ids, $context);
        }
    }

    private function cleanupDatabase(): void
    {
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('ALTER TABLE media DROP COLUMN storeMedia');
        $connection->executeStatement('ALTER TABLE country_state DROP COLUMN acrisStores');
        $connection->executeStatement('ALTER TABLE order_delivery DROP COLUMN acrisOrderDeliveryStore');
        $connection->executeStatement('ALTER TABLE media DROP COLUMN acrisStoreGroups');
        $connection->executeStatement('ALTER TABLE media DROP COLUMN acrisStoreGroupIcons');
        $connection->executeStatement('ALTER TABLE cms_page DROP COLUMN acrisStoreLocator');
        $connection->executeStatement('ALTER TABLE sales_channel DROP COLUMN acrisStoreLocator');
        $connection->executeStatement('ALTER TABLE rule DROP COLUMN acrisStoreLocator');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_store_media');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_store_rule');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_store_group_translation');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_order_delivery_store');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_store_locator_translation');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_store_sales_channel');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_store_locator');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_store_group');

    }

    public function insertDefaultValues(Context $context)
    {
        $storeGroupRepository = $this->container->get('acris_store_group.repository');

        $storeGroups =
            [[
                'display' => true,
                'displayBelowMap' => true,
                'active' => true,
                'default' => true,
                'position' => 'noDisplay',
                'priority' => 10,
                'icon_width' => 30,
                'icon_height' => 30,
                'icon_anchor_left' => 15,
                'icon_anchor_right' => 30,
                'groupZoomFactor' => 2,
                'fieldList' => ['name', 'department', 'phone', 'email', 'url', 'openingHours', 'city', 'zipcode', 'street', 'country'],
                'translations' => [
                    'de-DE' => [
                        'internalName' => 'Standard',
                        'name' => 'Standard'
                    ],
                    'en-GB' => [
                        'internalName' => 'Standard',
                        'name' => 'Standard'
                    ]
                ],
                'internalName' => 'Standard',
                'name' => 'Standard'
            ]];

        foreach ($storeGroups as $storeGroup) {
            $this->createIfNotExists($storeGroupRepository, [['name' => 'name', 'value' => $storeGroup['name']]], $storeGroup, $context);
        }
    }

    private function updateGroupForStores(Context $context): void
    {
        /** @var EntityRepository $storeRepository */
        $storeRepository = $this->container->get('acris_store_locator.repository');
        /** @var EntityRepository $storeGroupRepository */
        $storeGroupRepository = $this->container->get('acris_store_group.repository');
        $groupCriteria = new Criteria();
        $groupCriteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('default', true),
            new EqualsFilter('name', 'Standard')
        ]));
        $storeGroupId = $storeGroupRepository->searchIds($groupCriteria, $context)->firstId();

        if (empty($storeGroupId)) return;
        $storeCriteria = new Criteria();
        $storeCriteria->addFilter(new EqualsFilter('storeGroupId', null));
        $storeResult = $storeRepository->search($storeCriteria, $context);

        if ($storeResult->count() > 0 && $storeResult->first()) {
            /** @var StoreLocatorEntity $store */
            foreach ($storeResult as $store) {
                $storeRepository->update([
                    [
                        'id' => $store->getId(),
                        'storeGroupId' => $storeGroupId
                    ]
                ], $context);
            }
        }
    }

    private function updateImportExportProfile(Context $context): void
    {
        $importExportProfileRepository = $this->container->get('import_export_profile.repository');
        foreach ($this->getSystemDefaultProfilesForUpdate() as $profile) {
            $this->updateIfExists($importExportProfileRepository, [['name' => 'name', 'value' => $profile['name']]], $profile, $context);
        }
    }

    private function getSystemDefaultProfilesForUpdate(): array
    {
        $mapping = $this->getImportExportProfileMapping();

        return [
            [
                'name' => self::IMPORT_EXPORT_PROFILE_NAME,
                'mapping' => $mapping
            ],
        ];
    }

    private function updateIfExists(EntityRepository $repository, array $equalFields, array $data, Context $context)
    {
        $filters = [];
        foreach ($equalFields as $equalField) {
            $filters[] = new EqualsFilter($equalField['name'], $equalField['value']);
        }
        if(sizeof($filters) > 1) {
            $filter = new MultiFilter(MultiFilter::CONNECTION_OR, $filters);
        } else {
            $filter = array_shift($filters);
        }

        $searchResult = $repository->search((new Criteria())->addFilter($filter), $context);
        if($searchResult->count() > 0 && $searchResult->first()) {
            $data['id'] = $searchResult->first()->getId();
            $repository->update([$data], $context);
        }
    }

    private function cleanDefaultLayout(Context $context): void
    {
        $cmsPageRepository = $this->container->get('cms_page.repository');
        $connection = $this->container->get(Connection::class);

        $cmsPageName = self::DEFAULT_STORE_LAYOUT_CMS_PAGE_NAME;
        $existingSearchResult = $cmsPageRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $cmsPageName))->addFilter(new EqualsFilter('locked', true)), $context);
        if($existingSearchResult->getTotal() > 0) {

            $connection->executeStatement(
                'UPDATE cms_page SET locked = false WHERE id = (:id)',
                ['id' => Uuid::fromHexToBytes($existingSearchResult->firstId())]
            );

            $cmsPageRepository->delete([
                [
                    'id' => $existingSearchResult->firstId()
                ]
            ], $context);
        }
    }

    private function optimizeImportExportProfile(Context $context): void
    {
        $importExportProfileRepository = $this->container->get('import_export_profile.repository');
        foreach ($this->getOptimizedSystemDefaultProfiles() as $profile) {
            $this->createIfNotExists($importExportProfileRepository, [['name' => 'name', 'value' => $profile['name']]], $profile, $context);
        }
    }

    private function getOptimizedSystemDefaultProfiles(): array
    {
        $mapping = $this->getImportExportProfileMapping();

        return [
            [
                'name' => self::IMPORT_EXPORT_PROFILE_NAME,
                'label' => 'ACRIS Store Locator',
                'systemDefault' => true,
                'sourceEntity' => 'acris_store_locator',
                'fileType' => 'text/csv',
                'delimiter' => ';',
                'enclosure' => '"',
                'mapping' => $mapping,
                'translations' => [
                    'en-GB' => [
                        'label' => 'ACRIS Store Locator'
                    ],
                    'de-DE' => [
                        'label' => 'ACRIS Store Locator'
                    ]
                ],
            ],
        ];
    }

    private function createDefaultMediaUploadFolder(Context $context): void
    {
        $defaultMediaUploadDefaultFolderId = Uuid::randomHex();
        $defaultMediaUploadFolderId = Uuid::randomHex();

        $mediaDefaultFolderRepository = $this->container->get('media_default_folder.repository');
        $mediaFolderRepository = $this->container->get('media_folder.repository');

        $defaultExistingMediaUploadDefaultFolder = $this->getDefaultMediaUploadDefaultFolder($mediaDefaultFolderRepository, $context);

        if($defaultExistingMediaUploadDefaultFolder instanceof MediaDefaultFolderEntity) {
            return;
        }

        $defaultMediaUploadDefaultFolder = [
            'id' => $defaultMediaUploadDefaultFolderId,
            'associationFields' => self::DEFAULT_MEDIA_FOLDER_ASSOCIATION_FIELDS,
            'entity' => StoreLocatorDefinition::ENTITY_NAME,
            'customFields' => [
                self::DEFAULT_MEDIA_FOLDER_CUSTOM_FIELD => true
            ]
        ];
        $mediaDefaultFolderRepository->create([$defaultMediaUploadDefaultFolder], $context);

        $defaultExistingMediaUploadFolder = $this->getDefaultMediaUploadFolder($mediaFolderRepository, $context);

        if($defaultExistingMediaUploadFolder instanceof MediaFolderEntity) {
            return;
        }

        $defaultMediaUploadFolder = [
            'id' => $defaultMediaUploadFolderId,
            'name' => self::DEFAUL_MEDIA_FOLDER_NAME,
            'defaultFolderId'=> $defaultMediaUploadDefaultFolderId,
            'useParentConfiguration' => false,
            'configuration' => [
                'createThumbnails' => false
            ],
            'customFields' => [
                self::DEFAULT_MEDIA_FOLDER_CUSTOM_FIELD => true
            ]
        ];
        $mediaFolderRepository->create([$defaultMediaUploadFolder], $context);
    }

    private function removeMediaUploadFolder(Context $context): void
    {
        $mediaFolderRepository = $this->container->get('media_folder.repository');
        $defaultMediaUploadFolder = $this->getDefaultMediaUploadFolder($mediaFolderRepository, $context);

        if($defaultMediaUploadFolder instanceof MediaFolderEntity) {
            if($defaultMediaUploadFolder->getMedia() && $defaultMediaUploadFolder->getMedia()->count() > 0) {
                return;
            }

            $defaultMediaUploadFolderConfiguration = $defaultMediaUploadFolder->getConfiguration();
            $deleteConfigurationId = null;
            if($defaultMediaUploadFolderConfiguration && $defaultMediaUploadFolderConfiguration->getMediaFolders()) {
                if($defaultMediaUploadFolderConfiguration->getMediaFolders()->count() < 2) {
                    $deleteConfigurationId = $defaultMediaUploadFolderConfiguration->getId();
                }
            }
            $mediaFolderRepository->delete([['id' => $defaultMediaUploadFolder->getId()]], $context);

            if($deleteConfigurationId !== null) {
                $this->container->get('media_folder_configuration.repository')->delete([['id' => $deleteConfigurationId]], $context);
            }
        }

        try {
            $mediaDefaultFolderRepository = $this->container->get('media_default_folder.repository');
            $defaultMediaUploadDefaultFolder = $this->getDefaultMediaUploadDefaultFolder($mediaDefaultFolderRepository, $context);
            if($defaultMediaUploadDefaultFolder instanceof MediaDefaultFolderEntity) {
                $mediaDefaultFolderRepository->delete([['id' => $defaultMediaUploadDefaultFolder->getId()]], $context);
            }
        } catch (\Throwable $e) {}
    }

    private function getDefaultMediaUploadFolder(EntityRepository $mediaFolderRepository, Context $context): ?MediaFolderEntity
    {
        return $mediaFolderRepository->search((new Criteria())->addAssociation('media')->addAssociation('configuration')->addAssociation('configuration.mediaFolders')->addFilter(new EqualsFilter('customFields.'.self::DEFAULT_MEDIA_FOLDER_CUSTOM_FIELD, 'true')), $context)->first();
    }

    private function getDefaultMediaUploadDefaultFolder(EntityRepository $mediaFolderRepository, Context $context): ?MediaDefaultFolderEntity
    {
        return $mediaFolderRepository->search((new Criteria())->addFilter(new EqualsFilter('customFields.'.self::DEFAULT_MEDIA_FOLDER_CUSTOM_FIELD, 'true')), $context)->first();
    }

    private function getImportExportProfileMapping(): array
    {
        return [
            ['key' => 'id', 'mappedKey' => 'id'],
            ['key' => 'internalId', 'mappedKey' => 'internalId'],
            ['key' => 'active', 'mappedKey' => 'active'],
            ['key' => 'priority', 'mappedKey' => 'priority'],
            ['key' => 'storeGroup.translations.DEFAULT.name', 'mappedKey' => 'group'],
            ['key' => 'translations.DEFAULT.name', 'mappedKey' => 'companyName'],
            ['key' => 'translations.DEFAULT.department', 'mappedKey' => 'department'],
            ['key' => 'street', 'mappedKey' => 'street'],
            ['key' => 'zipcode', 'mappedKey' => 'zipcode'],
            ['key' => 'city', 'mappedKey' => 'city'],
            ['key' => 'country.translations.DEFAULT.name', 'mappedKey' => 'country'],
            ['key' => 'translations.DEFAULT.phone', 'mappedKey' => 'phone'],
            ['key' => 'translations.DEFAULT.email', 'mappedKey' => 'email'],
            ['key' => 'translations.DEFAULT.url', 'mappedKey' => 'url'],
            ['key' => 'translations.DEFAULT.opening_hours', 'mappedKey' => 'opening_hours'],
            ['key' => 'cmsPageId', 'mappedKey' => 'layoutId'],
            ['key' => 'translations.DEFAULT.seoUrl', 'mappedKey' => 'seoUrl'],
            ['key' => 'translations.DEFAULT.metaTitle', 'mappedKey' => 'metaTitle'],
            ['key' => 'translations.DEFAULT.metaDescription', 'mappedKey' => 'metaDescription'],
            ['key' => 'longitude', 'mappedKey' => 'longitude'],
            ['key' => 'latitude', 'mappedKey' => 'latitude']
        ];
    }

    private function insertDefaultValuesForImportExportPlugin(Context $context): void
    {
        if ($this->isImportExportPluginActive() !== true) {
            return;
        }

        $this->insertDefaultIdentifiers($context);
        $this->insertDefaultProcess($context);
        $this->insertDefaultReplacements($context);
    }

    private function insertDefaultIdentifiers(Context $context): void
    {
        /** @var EntityRepository $identifierRepository */
        $identifierRepository = $this->container->get('acris_import_export_identifier.repository');

        $defaultIdentifiers = [
            [
                'entity' => 'country',
                'identifier' => 'name',
                'priority' => 10,
                'active' => true
            ],[
                'entity' => 'acris_store_group',
                'identifier' => 'name',
                'priority' => 10,
                'active' => true
            ],
            [
                'entity' => 'acris_store_group',
                'identifier' => 'internalId',
                'priority' => 10,
                'active' => true
            ]
        ];

        foreach ($defaultIdentifiers as $defaultIdentifier) {
            $this->createIdentifierIfNotExists($identifierRepository, $context, $defaultIdentifier);
        }
    }

    private function createIdentifierIfNotExists(EntityRepository $entityRepository, Context $context, array $identifierData): void
    {
        $exists = $entityRepository->search((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('entity', $identifierData['entity']), new EqualsFilter('identifier', $identifierData['identifier'])])), $context);
        if($exists->getTotal() === 0) {
            $entityRepository->create([$identifierData], $context);
        }
    }

    private function insertDefaultProcess(Context $context)
    {
        /** @var EntityRepository $processRepository */
        $processRepository = $this->container->get('acris_import_export_process.repository');

        $filePathSync = $this->getPath() . '/Resources/default-values/import/store-locator-sync-api/v01/';
        $defaultSyncProcessFieldsV01 = $this->getDefaultStoreApiSyncConversionFields($filePathSync);

        $defaultProcesses = [
            [
                'name' => self::DEFAULT_SYNC_API_STORE_IMPORT_NAME_V01,
                'mode' => 'import',
                'importType' => self::PROCESS_TYPE_SYNC_API,
                'entity' => 'acris_store_locator',
                'active' => true,
                'processFields' => $defaultSyncProcessFieldsV01,
                'sendErrorMail' => true,
                'sorting' => 'modify',
                'isDefault' => true,
                'timeBetweenUploadAndImport' => 30,
                'maxTimeProcessRunning' => 86400,
                'maxTimeAfterLastImport' => 604800,
                'maxTimeOfOldImportedFiles' => 2592000
            ]
        ];

        foreach ($defaultProcesses as $defaultProcess) {
            $this->createProcessIfNotExists($processRepository, $context, $defaultProcess);
        }
    }

    private function getDefaultStoreApiSyncConversionFields($filePathSync): array
    {
        return [
            [
                'name' => 'media',
                'active' => true,
                'conversion' => file_get_contents($filePathSync.'media.php'),
                'dataType' => 'array',
                'required' => false,
                'addIfNotExists' => false,
                'addingOrder' => 50
            ],[
                'name' => 'cover',
                'active' => true,
                'conversion' => file_get_contents($filePathSync.'cover.php'),
                'dataType' => 'array',
                'required' => false,
                'addIfNotExists' => false,
                'addingOrder' => 50
            ],[
                'name' => 'addStoreId',
                'active' => true,
                'conversion' => file_get_contents($filePathSync.'addStoreId.php'),
                'dataType' => 'string',
                'required' => true,
                'addIfNotExists' => true,
                'addingOrder' => 1
            ],[
                'name' => 'storeGroup',
                'active' => true,
                'conversion' => file_get_contents($filePathSync.'storeGroup.php'),
                'dataType' => 'string',
                'required' => false,
                'addIfNotExists' => true,
                'addingOrder' => 10
            ],[
                'name' => 'state',
                'active' => true,
                'conversion' => file_get_contents($filePathSync.'state.php'),
                'dataType' => 'string',
                'required' => false,
                'addIfNotExists' => false,
                'addingOrder' => 10
            ]
        ];
    }

    private function createProcessIfNotExists(EntityRepository $entityRepository, Context $context, array $processData): void
    {
        $exists = $entityRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $processData['name'])), $context);
        if($exists->getTotal() === 0) {
            $entityRepository->create([$processData], $context);
        }
    }

    private function removeDefaultValuesForImportExportPlugin(Context $context)
    {
        if ($this->isImportExportPluginActive() !== true) {
            return;
        }

        $this->removeDefaultIdentifiers($context);
        $this->removeDefaultProcess($context);
        $this->removeDefaultReplacements($context);
    }

    private function removeDefaultIdentifiers(Context $context)
    {
        /** @var EntityRepository $identifierRepository */
        $identifierRepository = $this->container->get('acris_import_export_identifier.repository');

        $searchResult = $identifierRepository->searchIds((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('entity', 'acris_store_group')
        ])), $context);

        $ids = [];

        if ($searchResult->getTotal() > 0) {
            foreach ($searchResult->getIds() as $id) {
                $ids[] = ['id' => $id];
            }
            $identifierRepository->delete($ids, $context);
        }
    }

    private function removeDefaultProcess(Context $context)
    {
        /** @var EntityRepository $processRepository */
        $processRepository = $this->container->get('acris_import_export_process.repository');

        $searchResult = $processRepository->searchIds((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('name', self::DEFAULT_SYNC_API_STORE_IMPORT_NAME_V01)
        ])), $context);

        $ids = [];

        if ($searchResult->getTotal() > 0) {
            foreach ($searchResult->getIds() as $id) {
                $ids[] = ['id' => $id];
            }
            $processRepository->delete($ids, $context);
        }
    }

    private function insertDefaultReplacements(Context $context): void
    {
        /** @var EntityRepository $replacementRepository */
        $replacementRepository = $this->container->get('acris_import_export_replacement.repository');

        $defaultReplacements = [
            [
                'entity' => 'acris_store_locator',
                'propertyName' => 'media',
                'active' => true
            ]
        ];

        foreach ($defaultReplacements as $defaultReplacement) {
            $this->createReplacementIfNotExists($replacementRepository, $context, $defaultReplacement);
        }
    }

    private function createReplacementIfNotExists(EntityRepository $entityRepository, Context $context, array $replacementData): void
    {
        $exists = $entityRepository->search((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('entity', $replacementData['entity']), new EqualsFilter('propertyName', $replacementData['propertyName'])])), $context);
        if($exists->getTotal() === 0) {
            $entityRepository->create([$replacementData], $context);
        }
    }

    private function removeDefaultReplacements(Context $context): void
    {
        /** @var EntityRepository $replacementRepository */
        $replacementRepository = $this->container->get('acris_import_export_replacement.repository');

        $searchResult = $replacementRepository->searchIds((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('entity', 'acris_store_locator')
        ])), $context);

        $ids = [];

        if ($searchResult->getTotal() > 0) {
            foreach ($searchResult->getIds() as $id) {
                $ids[] = ['id' => $id];
            }
            $replacementRepository->delete($ids, $context);
        }
    }

    private function updateStateConversionField(Context $context)
    {
        if ($this->isImportExportPluginActive() !== true) {
            return;
        }

        /** @var EntityRepository $processFieldRepository */
        $processFieldRepository = $this->container->get('acris_import_export_process_field.repository');

        $criteria = new Criteria();
        $criteria->addAssociation('process')
            ->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('process.name', self::DEFAULT_SYNC_API_STORE_IMPORT_NAME_V01),
                new MultiFilter(MultiFilter::CONNECTION_OR, [
                    new EqualsFilter('name', 'state')
                ])
            ]));

        $processFieldId = $processFieldRepository->searchIds($criteria, $context)->firstId();

        if (empty($processFieldId)) return;

        $filePath = $this->getPath() . '/Resources/default-values/import/store-locator-sync-api/v01/';

        $processFieldRepository->update([
            [
                'id' => $processFieldId,
                'conversion' => file_get_contents($filePath.'state.php')
            ]
        ], $context);
    }

    private function isImportExportPluginActive(): bool
    {
        $kernelPluginCollection = $this->container->get('Shopware\Core\Framework\Plugin\KernelPluginCollection');

        /** @var AcrisImportExport $importExportPlugin */
        $importExportPlugin = $kernelPluginCollection->get(AcrisImportExport::class);

        /** @var AcrisImportExportCS $importExportPlugin */
        $importExportPluginCS = $kernelPluginCollection->get(AcrisImportExportCS::class);

        return $importExportPlugin !== null && $importExportPlugin->isActive() !== false || $importExportPluginCS !== null && $importExportPluginCS->isActive() !== false;
    }
}
