<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Components;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\DataResolver\CmsSlotsDataResolver;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionCollection;

class LexiconCMSHelper
{
    public function __construct(
        private readonly CmsSlotsDataResolver $slotDataResolver,
        private readonly EntityRepository $cmsPageRepository
    ) {

    }

    public function getCmsPage(?string $id, Context $context, string $pagetype): ?CmsPageEntity
    {
        if (!empty($id)) {
            $criteria = new Criteria();
            $criteria->setTitle('Lexicon getCmsPage');
            $criteria->addFilter(new EqualsFilter('id', $id));
            $criteria->addAssociation('sections');
            $criteria->addAssociation('sections.blocks');

            $criteria
                ->getAssociation('sections')
                ->addAssociation('backgroundMedia');

            $criteria
                ->getAssociation('sections.blocks')
                ->addAssociation('backgroundMedia')
                ->addAssociation('slots');

            $cmsPage = $this->cmsPageRepository->search($criteria, $context)->first();
        }

        if (empty($cmsPage)) {
            $nameList = match($pagetype) {
                'index' => ['Coolbax Lexikon Übersicht', 'Coolbax Lexicon Overview'],
                'detail' => ['Coolbax Lexikon Detail', 'Coolbax Lexicon Detail'],
                'listing' => ['Coolbax Lexikon Listing', 'Coolbax Lexicon Listing'],
                'content' => ['Coolbax Lexikon Inhalt', 'Coolbax Lexicon Content'],
                'default' => ['Coolbax Lexikon Übersicht', 'Coolbax Lexicon Overview']
            };
            $criteria = new Criteria();
            $criteria->setTitle('Lexicon getCmsPage');
            $criteria->addFilter(new EqualsFilter('locked', 1));
            $criteria->addFilter(new EqualsFilter('type', 'cbax_lexicon'));
            $criteria->addFilter(new EqualsAnyFilter('name', $nameList));
            $criteria->addAssociation('sections');
            $criteria->addAssociation('sections.blocks');

            $criteria
                ->getAssociation('sections')
                ->addAssociation('backgroundMedia');

            $criteria
                ->getAssociation('sections.blocks')
                ->addAssociation('backgroundMedia')
                ->addAssociation('slots');

            $cmsPage = $this->cmsPageRepository->search($criteria, $context)->first();
        }

        return $cmsPage;
    }

    public function loadSlotData(CmsPageEntity $page, ResolverContext $resolverContext): void
    {
        $slots = $this->slotDataResolver->resolve($page->getSections()->getBlocks()->getSlots(), $resolverContext);
        $page->getSections()->getBlocks()->setSlots($slots);
    }

    public function filterEmptyCmsBlocks(LexiconPage $page): LexiconPage
    {
        //Blöcke mit element mit data = null
        $blocksNotToFilter = ['category-navigation', 'sidebar-filter'];
        $cmsPage = $page->getCmsPage();
        if (empty($cmsPage)) return $page;
        if (empty($cmsPage->getSections())) return $page;
        if (empty($cmsPage->getSections()->getBlocks())) return $page;

        $cmsSectionCollection = new CmsSectionCollection();

        foreach ($cmsPage->getSections() as $section) {
            $newBlocksCollection = $section->getBlocks();

            foreach ($section->getBlocks() as $block) {
                if (in_array($block->getType(), $blocksNotToFilter)) continue;
                $blockIsEmpty = true;

                foreach ($block->getSlots() as $slot) {
                    if (!empty($slot->getData())) {
                        foreach ($slot->getData()->getVars() as $var) {
                            if (!empty($var)) {
                                $blockIsEmpty = false;
                                break;
                            }
                        }
                    }

                    if (!$blockIsEmpty) break;
                }

                if ($blockIsEmpty) {
                    $newBlocksCollection->filterAndReduceByProperty('id', $block->getId());
                }
            }

            $section->setBlocks($newBlocksCollection);
            $cmsSectionCollection->add($section);
        }

        $cmsPage->setSections($cmsSectionCollection);
        $page->setCmsPage($cmsPage);

        return $page;
    }
}

