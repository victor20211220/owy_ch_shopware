<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Bootstrap;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;

class CmsPageCreator
{
    private $systemlanguageId;

    /**
     * @param     array    $services
     * @param     Context   $context
     */
    public function createDefaultLexiconCmsPages($services, $context)
    {
        $this->systemlanguageId = $context->getLanguageId();
        $cmsPageRepository = $services['cmsPageRepository'];
        $languageRepository = $services['languageRepository'];

        // originalpage holen
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', 'cbax_lexicon'));
        $criteria->addFilter(new EqualsFilter('locked', 1));

        $foundIds = $cmsPageRepository->searchIds($criteria, $context)->getIds();

        if (!empty($foundIds)) { return; }

        $languageCriteria = new Criteria();
        $languageCriteria->addFilter(new EqualsFilter('name', 'Deutsch'));

        $languageGerman = $languageRepository->search($languageCriteria, $context)->first();

        if (!empty($languageGerman)) {
            $languageGermanId = $languageGerman->getId();
        } else {
            $languageGermanId = null;
        }

        $languageCriteria = new Criteria();
        $languageCriteria->addFilter(new EqualsFilter('name', 'English'));

        $languageEnglish = $languageRepository->search($languageCriteria, $context)->first();

        if (!empty($languageEnglish)) {
            $languageEnglishId = $languageEnglish->getId();
        } else {
            $languageEnglishId = null;
        }

        ////////////////////////////////////////////////////////////////////////////////
        // Index Seite Sidebar
        // Index Seite Banner Section
        $slotImageCoverIndexConfig = [
            'url' => ['value' => null, 'source' => 'static'],
            'media' => ['value' => null, 'source' => 'static'],
            'newTab' => ['value' => null, 'source' => 'static'],
            'minHeight' => ['value' => '340px', 'source' => 'static'],
            'displayMode' => ['value' => 'cover', 'source' => 'static'],
            'verticalAlign' => ['value' => null, 'source' => 'static']
        ];
        $slotImageCoverIndex = $this->setCmsSlot('image', 'image', $languageEnglishId, $languageGermanId, $slotImageCoverIndexConfig);
        $blockImageCoverIndex = $this->setCmsBlock('image-cover', 0, 'main', [$slotImageCoverIndex]);
        $sectionDefaultIndex = $this->setCmsSection('default', 0, [$blockImageCoverIndex]);

        // Index Seite Inhalt Section
        // Index Seite Inhalt Section Sidebar
        // Index Seite Inhalt Section Sidebar CategoryNavigation
        $slotCategoryNavigationIndex = $this->setCmsSlot('category-navigation', 'content', $languageEnglishId, $languageGermanId);
        $blockCategoryNavigationIndex = $this->setCmsBlock('category-navigation', 0, 'sidebar', [$slotCategoryNavigationIndex]);

        // Index Seite Inhalt Section main Navigation
        $slotNavigationIndexConfig = [
            'stop' => ['value' => 5, 'source' => 'static'],
            'content' => ['value' => null, 'source' => 'static'],
            'duration' => ['value' => 500, 'source' => 'static'],
            'navigation' => ['value' => 'scroll', 'source' => 'static']
        ];
        $slotNavigationIndex = $this->setCmsSlot('cbax-lexicon-navigation', 'content', $languageEnglishId, $languageGermanId, $slotNavigationIndexConfig);
        $blockNavigationIndex = $this->setCmsBlock('cbax-lexicon-navigation', 2, 'main', [$slotNavigationIndex]);

        // Index Seite Inhalt Section main
        // Index Seite Inhalt Section main headline
        $slotTextIndexConfigGerman = [
            'content' => ['value' => '<h2>Lexikon Übersicht<br></h2>', 'source' => 'static'],
            'verticalAlign' => ['value' => null, 'source' => 'static']
        ];
        $slotTextIndexConfigEnglish = [
            'content' => ['value' => '<h2>Lexicon Overview<br></h2>', 'source' => 'static'],
            'verticalAlign' => ['value' => null, 'source' => 'static']
        ];
        $slotTextIndex = $this->setCmsSlot('text', 'content', $languageEnglishId, $languageGermanId, $slotTextIndexConfigGerman, $slotTextIndexConfigEnglish);
        $blockTextIndex = $this->setCmsBlock('text', 3, 'main', [$slotTextIndex]);

        // Index Seite Inhalt Section main Listing latest
        $slotListingLatestIndexConfigGerman = [
            'template' => ['value' => 'listing_3col', 'source' => 'static'],
            'entryNumber' => ['value' => 3, 'source' => 'static'],
            'headline' => ['value' => 'Die neusten Einträge', 'source' => 'static'],
			'buttonVariant' => ['value' => 'btn-outline-secondary', 'source' => 'static'],
			'buttonSize' => ['value' => 'btn-sm', 'source' => 'static']
        ];
        $slotListingLatestIndexConfigEnglish = [
            'template' => ['value' => 'listing_3col', 'source' => 'static'],
            'entryNumber' => ['value' => 3, 'source' => 'static'],
            'headline' => ['value' => 'The latest entries', 'source' => 'static'],
			'buttonVariant' => ['value' => 'btn-outline-secondary', 'source' => 'static'],
			'buttonSize' => ['value' => 'btn-sm', 'source' => 'static']
        ];
        $slotListingLatestIndex = $this->setCmsSlot('cbax-lexicon-latest-entries', 'content', $languageEnglishId, $languageGermanId, $slotListingLatestIndexConfigGerman, $slotListingLatestIndexConfigEnglish);
        $blockListingLatestIndex = $this->setCmsBlock('cbax-lexicon-latest-entries', 4, 'main', [$slotListingLatestIndex]);

        // Index Seite Inhalt Section main Listing popular
        $slotListingPopularIndexConfigGerman = [
            'template' => ['value' => 'listing_3col', 'source' => 'static'],
            'entryNumber' => ['value' => 3, 'source' => 'static'],
            'headline' => ['value' => 'Die meistgelesenen Einträge', 'source' => 'static']
        ];
        $slotListingPopularIndexConfigEnglish = [
            'template' => ['value' => 'listing_3col', 'source' => 'static'],
            'entryNumber' => ['value' => 3, 'source' => 'static'],
            'headline' => ['value' => 'The most read entries', 'source' => 'static']
        ];
        $slotListingPopularIndex = $this->setCmsSlot('cbax-lexicon-popular-entries', 'content', $languageEnglishId, $languageGermanId, $slotListingPopularIndexConfigGerman, $slotListingPopularIndexConfigEnglish);
        $blockListingPopularIndex = $this->setCmsBlock('cbax-lexicon-popular-entries', 5, 'main', [$slotListingPopularIndex]);

        $sectionSidebarIndex = $this->setCmsSection('sidebar', 1,
            [$blockCategoryNavigationIndex, $blockNavigationIndex, $blockTextIndex, $blockListingLatestIndex, $blockListingPopularIndex]);

        $cmsPageIndex = $this->setCmsPage('cbax_lexicon', [$sectionDefaultIndex, $sectionSidebarIndex], $languageEnglishId, $languageGermanId, 'Coolbax Lexikon Übersicht', 'Coolbax Lexicon Overview');

        // Ende Index Seite Sidebar
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////
        // Listing Seite Sidebar
        // Index Seite Banner Section
        $sectionDefaultListing = $sectionDefaultIndex;

        // Listing Seite Inhalt Section
        // Listing Seite Inhalt Section Sidebar
        // Listing Seite Inhalt Section Sidebar CategoryNavigation
        $blockCategoryNavigationListing = $blockCategoryNavigationIndex;

        // Listing Seite Inhalt Section main
        // Listing Seite Inhalt Section main Navigation
        $blockNavigationListing = $blockNavigationIndex;

        // Listing Seite Inhalt Section main Listing für den Buchstaben
        $slotListingLetterListingConfig = [
            'template' => ['value' => 'listing_3col', 'source' => 'static'],
			'buttonVariant' => ['value' => 'btn-outline-secondary', 'source' => 'static'],
			'buttonSize' => ['value' => 'btn-sm', 'source' => 'static']
        ];

        $slotListingLetterListing = $this->setCmsSlot('cbax-lexicon-letter-entries', 'content', $languageEnglishId, $languageGermanId, $slotListingLetterListingConfig);
        $blockListingLetterListing = $this->setCmsBlock('cbax-lexicon-letter-entries', 4, 'main', [$slotListingLetterListing]);

        $sectionSidebarListing = $this->setCmsSection('sidebar', 1,
            [$blockCategoryNavigationListing, $blockNavigationListing, $blockListingLetterListing]);

        $cmsPageListing = $this->setCmsPage('cbax_lexicon', [$sectionDefaultListing, $sectionSidebarListing], $languageEnglishId, $languageGermanId, 'Coolbax Lexikon Listing', 'Coolbax Lexicon Listing');

        // Ende Listing Seite Sidebar
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////
        // Content Seite Sidebar
        // Content Seite Banner Section
        $sectionDefaultContent = $sectionDefaultIndex;

        // Content Seite Inhalt Section
        // Content Seite Inhalt Section Sidebar
        // Content Seite Inhalt Section Sidebar CategoryNavigation
        $blockCategoryNavigationContent = $blockCategoryNavigationIndex;

        // Content Seite Inhalt Section main Navigation
        $blockNavigationContent = $blockNavigationIndex;

        // Content Seite Inhalt Section main
        // Content Seite Inhalt Section main headline
        $slotTextContentConfigGerman = [
            'content' => ['value' => '<h2>Inhaltsverzeichnis<br></h2>', 'source' => 'static'],
            'verticalAlign' => ['value' => null, 'source' => 'static']
        ];
        $slotTextContentConfigEnglish = [
            'content' => ['value' => '<h2>Table of Content<br></h2>', 'source' => 'static'],
            'verticalAlign' => ['value' => null, 'source' => 'static']
        ];
        $slotTextContent = $this->setCmsSlot('text', 'content', $languageEnglishId, $languageGermanId, $slotTextContentConfigGerman, $slotTextContentConfigEnglish);
        $blockTextContent = $this->setCmsBlock('text', 3, 'main', [$slotTextContent]);

        // Content Seite Inhalt Section main Inhalt
        $slotContentContent = $this->setCmsSlot('cbax-lexicon-content', 'content', $languageEnglishId, $languageGermanId);
        $blockContentContent = $this->setCmsBlock('cbax-lexicon-content', 4, 'main', [$slotContentContent]);

        $sectionSidebarContent = $this->setCmsSection('sidebar', 1,
            [$blockCategoryNavigationContent, $blockNavigationContent, $blockTextContent, $blockContentContent]);

        $cmsPageContent = $this->setCmsPage('cbax_lexicon', [$sectionDefaultContent, $sectionSidebarContent], $languageEnglishId, $languageGermanId, 'Coolbax Lexikon Inhalt', 'Coolbax Lexicon Content');

        // Ende Content Seite Sidebar
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Detail Seite Sidebar
        // Detail Seite Banner Section
        $slotLexiconBannerDetailConfig = [
            'url' => ['value' => null, 'source' => 'static'],
            'media' => ['value' => 'cbax_lexicon_entry.media2', 'source' => 'mapped'],
            'newTab' => ['value' => null, 'source' => 'static'],
            'minHeight' => ['value' => '340px', 'source' => 'static'],
            'displayMode' => ['value' => 'cover', 'source' => 'static'],
            'verticalAlign' => ['value' => null, 'source' => 'static']
        ];
        $slotLexiconBannerDetail = $this->setCmsSlot('image', 'image', $languageEnglishId, $languageGermanId, $slotLexiconBannerDetailConfig);
        $blockLexiconBannerDetail = $this->setCmsBlock('image-cover', 0, 'main', [$slotLexiconBannerDetail]);
        $sectionDefaultDetail = $this->setCmsSection('default', 0, [$blockLexiconBannerDetail]);

        // Detail Seite Inhalt Section
        // Detail Seite Inhalt Section Sidebar
        // Detail Seite Inhalt Section Sidebar CategoryNavigation
        $blockCategoryNavigationDetail = $blockCategoryNavigationIndex;

        // Detail Seite Inhalt Section Sidebar Lexikon Sidebar
        $slotSidebarDetail = $this->setCmsSlot('cbax-lexicon-sidebar', 'content', $languageEnglishId, $languageGermanId);
        $blockSidebarDetail = $this->setCmsBlock('cbax-lexicon-sidebar', 1, 'sidebar', [$slotSidebarDetail]);

        // Detail Seite Inhalt Section main
        // Detail Seite Inhalt Section main Navigation
        $blockNavigationDetail = $blockNavigationIndex;

        // Detail Seite Inhalt Section main Logo-Description
        /*
        $slotImageLogoDetailConfig = [
            'url' => ['value' => null, 'source' => 'static'],
            'media' => ['value' => 'cbax_lexicon_entry.media3', 'source' => 'mapped'],
            'newTab' => ['value' => null, 'source' => 'static'],
            'minHeight' => ['value' => '340px', 'source' => 'static'],
            'displayMode' => ['value' => 'standard', 'source' => 'static'],
            'verticalAlign' => ['value' => null, 'source' => 'static']
        ];
        $slotImageLogoDetail = $this->setCmsSlot('image', 'left', $languageEnglishId, $languageGermanId, $slotImageLogoDetailConfig);
        $slotTextDescDetailConfig = [
            'content' => ['value' => 'cbax_lexicon_entry.attribute1', 'source' => 'mapped'],
            'verticalAlign' => ['value' => null, 'source' => 'static']
        ];
        $slotTextDescDetail = $this->setCmsSlot('text', 'right', $languageEnglishId, $languageGermanId, $slotTextDescDetailConfig);
        $blockImageTextDetail = $this->setCmsBlock('image-text', 3, 'main', [$slotImageLogoDetail, $slotTextDescDetail]);
        */

        // Detail Seite Inhalt Section main Entry
        $slotEntryDetail = $this->setCmsSlot('cbax-lexicon-entry', 'content', $languageEnglishId, $languageGermanId);
        $blockEntryDetail = $this->setCmsBlock('cbax-lexicon-entry', 4, 'main', [$slotEntryDetail]);

        // Detail Seite Inhalt Section main Products
        $slotProductsDetail = $this->setCmsSlot('cbax-lexicon-products', 'content', $languageEnglishId, $languageGermanId);
        $blockProductsDetail = $this->setCmsBlock('cbax-lexicon-products', 5, 'main', [$slotProductsDetail]);

        $sectionSidebarDetail = $this->setCmsSection('sidebar', 1,
            [$blockCategoryNavigationDetail, $blockSidebarDetail, $blockNavigationDetail, /*$blockImageTextDetail,*/ $blockEntryDetail, $blockProductsDetail]);

        $cmsPageDetail = $this->setCmsPage('cbax_lexicon', [$sectionDefaultDetail, $sectionSidebarDetail], $languageEnglishId, $languageGermanId, 'Coolbax Lexikon Detail', 'Coolbax Lexicon Detail');

        // Ende Detail
        //////////////////////////////////////////////////////////////////////////////////////////////////////////

        $cmsPageRepository->create([$cmsPageIndex, $cmsPageListing, $cmsPageContent, $cmsPageDetail], $context);

        //locked mit sql setzen, geht nicht über DAL
        $sql = "UPDATE `cms_page` SET `locked` = 1 WHERE `type` = 'cbax_lexicon' AND `updated_at` IS NULL;";
        $connection = $services['connectionService'];
        $connection->executeStatement($sql);
    }

    /**
     * @param     array    $services
     * @param     Context   $context
     */
    public function updateLexiconCmsPages($services, $context)
    {
    }

    private function setCmsSlot($type, $slotName, $languageEnglishId, $languageGermanId, $configGer = null, $configEng = null): array
    {
        $slot = [];
        $slot['type'] = $type;
        $slot['slot'] = $slotName;
        if ($this->systemlanguageId == $languageGermanId || empty($configEng))
        {
            $slot['config'] = $configGer;
        } else {
            $slot['config'] = $configEng;
        }

        if (!empty($configGer) && !empty($languageGermanId) && $this->systemlanguageId != $languageGermanId)
        {
            $slot['translations'] = [['languageId' => $languageGermanId, 'config' => $configGer]];
        }
        if (!empty($configEng) && !empty($languageEnglishId) && $this->systemlanguageId != $languageEnglishId)
        {
            $slot['translations'] = [['languageId' => $languageEnglishId, 'config' => $configEng]];
        }

        return $slot;
    }

    private function setCmsBlock($type, $position, $sectionPosition, $slots): array
    {
        $block = [];
        $block['marginTop'] = '20px';
        $block['marginBottom'] = '20px';
        $block['marginLeft'] = '20px';
        $block['marginRight'] = '20px';
        $block['type'] = $type;
        $block['position'] = $position;
        $block['sectionPosition'] = $sectionPosition;
        $block['slots'] = $slots;

        return $block;
    }

    private function setCmsSection($type, $position, $blocks): array
    {
        $section = [];
        $section['type'] = $type;
        $section['position'] = $position;
        $section['blocks'] = $blocks;

        return $section;
    }

    private function setCmsPage($type, $sections, $languageEnglishId, $languageGermanId, $nameGerman, $nameEnglish): array
    {
        $page = [];
        $page['type'] = $type;
        $page['sections'] = $sections;

        if ($this->systemlanguageId == $languageGermanId || empty($nameEnglish))
        {
            $page['name'] = $nameGerman;
        } else {
            $page['name'] = $nameEnglish;
        }

        if (!empty($nameGerman) && !empty($languageGermanId) && $this->systemlanguageId != $languageGermanId)
        {
            $page['translations'] = [['languageId' => $languageGermanId, 'name' => $nameGerman]];
        }
        if (!empty($nameEnglish) && !empty($languageEnglishId) && $this->systemlanguageId != $languageEnglishId)
        {
            $page['translations'] = [['languageId' => $languageEnglishId, 'name' => $nameEnglish]];
        }

        return $page;
    }

    /**
     * @param     array    $services
     * @param     Context   $context
     */
    public function deleteLexiconCmsPages($services, $context)
    {
        $sql = "UPDATE `cms_page` SET `locked` = 0 WHERE `type` = 'cbax_lexicon' AND `locked` = 1;";
        $connection = $services['connectionService'];
        $connection->executeStatement($sql);

        $cmsPageRepository = $services['cmsPageRepository'];

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', 'cbax_lexicon'));

        $ids = $cmsPageRepository->searchIds($criteria, $context)->getIds();

        if (empty($ids))
        {
            return;
        }

        $cmsPageRepository->delete(array_map(function (string $id) {
            return ['id' => $id];
        }, $ids), $context);
    }

    /**
     * @param     array    $services
     * @param     Context   $context
     */
    public function deleteLexiconCmsBlocks($services, $context) {
        $sql = "UPDATE `cms_block` SET `locked` = 0 WHERE `type` LIKE '%cbax-lexicon-%' AND `locked` = 1;";
        $connection = $services['connectionService'];
        $connection->executeStatement($sql);

        $cmsBlockRepository = $services['cmsBlockRepository'];

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('type', 'cbax-lexicon-'));

        $ids = $cmsBlockRepository->searchIds($criteria, $context)->getIds();

        if (empty($ids)) { return; }

        $cmsBlockRepository->delete(array_map(function (string $id) {
            return ['id' => $id];
        }, $ids), $context);
    }

    /**
     * @param     array    $services
     * @param     Context   $context
     */
    public function deleteLexiconCmsSlots($services, $context) {
        $sql = "UPDATE `cms_slot` SET `locked` = 0 WHERE `type` LIKE '%cbax-lexicon-%' AND `locked` = 1;";
        $connection = $services['connectionService'];
        $connection->executeStatement($sql);

        $cmsSlotRepository = $services['cmsSlotRepository'];

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('type', 'cbax-lexicon-'));

        $ids = $cmsSlotRepository->searchIds($criteria, $context)->getIds();

        if (empty($ids))
        {
            return;
        }

        $cmsSlotRepository->delete(array_map(function (string $id) {
            return ['id' => $id];
        }, $ids), $context);
    }
}

