<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Shopware\Core\Framework\Context;
use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;

use Cbax\ModulLexicon\Components\LexiconHelper;
use Cbax\ModulLexicon\Components\LexiconCMSHelper;
use Cbax\ModulLexicon\Components\LexiconReplacer;
use Cbax\ModulLexicon\Components\LexiconSeo;
use Cbax\ModulLexicon\Components\LexiconPage;
use Cbax\ModulLexicon\Core\Content\Bundle\LexiconEntryDefinition;

class FrontendController extends StorefrontController
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';

    private $config = null;

    public function __construct(
        private readonly LexiconHelper $lexiconHelper,
        private readonly LexiconCMSHelper $cmsHelper,
        private readonly LexiconSeo $lexiconSeo,
        private readonly GenericPageLoaderInterface $genericPageLoader,
        private readonly LexiconReplacer $lexiconReplacer,
        private readonly LexiconEntryDefinition $lexiconEntryDefinition,
        Environment $twig
    ) {
        $this->setTwig($twig);
    }

	/**
	* @Route("/cbax/lexicon/index",
     *     name="frontend.cbax.lexicon.index",  options={"seo"=true}, methods={"GET"},
     *     defaults={"auth_required"=false, "_httpCache"=true, "_routeScope"={"storefront"}})
	*/
    public function index(Request $request, Context $context): Response
    {
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $this->config = $this->config ?? $this->getSystemConfigService()->get(self::CONFIG_PATH, $salesChannelId);

        if (empty($this->config['active'])) return $this->forwardToRoute('frontend.home.page');

        $cmsPageId = $this->config['cmsPageIndex'] ?? null;

		// Standard Page Object für Breadcrumb erzeugen
        $newPage = $this->genericPageLoader->load($request, $salesChannelContext);
        $page = LexiconPage::createFrom($newPage);

        //CMS Page
        $cmsPage = $this->cmsHelper->getCmsPage($cmsPageId, $context, 'index');
        if (empty($cmsPage)) return $this->forwardToRoute('frontend.home.page');

        if ($cmsPage->getSections())
        {
            $cmsPage->getSections()->sort(function (CmsSectionEntity $a, CmsSectionEntity $b) {
                return $a->getPosition() <=> $b->getPosition();
            });

            $resolverContext = new ResolverContext($salesChannelContext, $request);

            // sort blocks into sectionPositions
            foreach ($cmsPage->getSections() as $section) {
                $section->getBlocks()?->sort(function (CmsBlockEntity $a, CmsBlockEntity $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });
            }

            // find config overwrite
            //$overwrite = $config[$page->getId()] ?? $config;

            // overwrite slot config
            //$this->overwriteSlotConfig($page, $overwrite);

            // resolve slot data
            $this->cmsHelper->loadSlotData($cmsPage, $resolverContext);
        }

        $page->setCmsPage($cmsPage);

        $cbaxModulLexicon['page'] = 'index';
        $linkIndex = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.index', '/cbax/lexicon/index', $context, $request);
		$breadcrumbLinkName = $this->container->get('translator')->trans('cbaxLexicon.page.breadcrumb.name');

		$breadcrumb[] = array(
            'link' => $linkIndex,
            'name' => $breadcrumbLinkName,
			'active' => true,
            'translated' => array(
                'name' => $breadcrumbLinkName
            )
        );

        //Meta
        if ($page->getMetaInformation()) {
            $metaDescription = $this->getSnippet("cbaxLexicon.page.index.metaDescription", "Lexicon Index");
            $page->getMetaInformation()->setMetaDescription($metaDescription);

            $metaKeyword = $this->getSnippet("cbaxLexicon.page.index.metaKeywords", "Lexicon Index");
            $page->getMetaInformation()->setMetaKeywords($metaKeyword);

            $metaTitle = $this->getSnippet("cbaxLexicon.page.index.metaTitle", "Lexicon");
            $page->getMetaInformation()->setMetaTitle($metaTitle);
            $page->getMetaInformation()->setCanonical($linkIndex);
        }

        $page = $this->cmsHelper->filterEmptyCmsBlocks($page);

		return $this->renderStorefront('@Storefront/storefront/base.html.twig', [
            'cbaxModulLexicon' => $cbaxModulLexicon,
            'page' => $page,
			'breadcrumbList' => $breadcrumb
        ]);
    }

	/**
	* @Route("/cbax/lexicon/listing/{char?}",
     *     name="frontend.cbax.lexicon.listing", options={"seo"=true}, methods={"GET"},
     *     defaults={"auth_required"=false, "_httpCache"=true, "_routeScope"={"storefront"}})
	*/
    public function listing(Request $request, Context $context): Response
    {
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $this->config = $this->config ?? $this->getSystemConfigService()->get(self::CONFIG_PATH, $salesChannelId);

        if (empty($this->config['active'])) return $this->forwardToRoute('frontend.home.page');


        $char = $request->attributes->get('char');
        if (empty($char)) return $this->forwardToRoute('frontend.cbax.lexicon.index');

        $cmsPageId = $this->config['cmsPageListing'] ?? null;

        // Standard Page Object für Breadcrumb erzeugen
        $newPage = $this->genericPageLoader->load($request, $salesChannelContext);
        $page = LexiconPage::createFrom($newPage);

        //CMS Page
        $cmsPage = $this->cmsHelper->getCmsPage($cmsPageId, $context, 'listing');
        if (empty($cmsPage)) return $this->forwardToRoute('frontend.cbax.lexicon.index');

        if ($cmsPage->getSections())
        {
            $cmsPage->getSections()->sort(function (CmsSectionEntity $a, CmsSectionEntity $b) {
                return $a->getPosition() <=> $b->getPosition();
            });

            $resolverContext = new ResolverContext($salesChannelContext, $request);

            // sort blocks into sectionPositions
            foreach ($cmsPage->getSections() as $section) {
                $section->getBlocks()?->sort(function (CmsBlockEntity $a, CmsBlockEntity $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });
            }

            // resolve slot data
            $this->cmsHelper->loadSlotData($cmsPage, $resolverContext);
        }

        $page->setCmsPage($cmsPage);

        $cbaxModulLexicon['page']       = 'listing';
        $cbaxModulLexicon['char'] 		= $char;
		$linkIndex = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.index', '/cbax/lexicon/index', $context, $request);
		$linkListing = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.listing' , '/cbax/lexicon/listing/' . $char, $context, $request);
		$breadcrumbLinkName = $this->container->get('translator')->trans('cbaxLexicon.page.breadcrumb.name');

		$breadcrumb[] = array(
            'link' => $linkIndex,
            'name' => $breadcrumbLinkName,
            'translated' => array(
                'name' => $breadcrumbLinkName
            )
        );
		$breadcrumb[] = array(
            'link' => $linkListing,
            'name' => $char,
			'active' => true,
			'translated' => array(
				'name' => $char
			)
        );

        //Meta
        if ($page->getMetaInformation()) {
            $charForSnippet = str_replace('-', '', $char);
            $metaDescription = $this->getSnippet("cbaxLexicon.page.listing.metaDescription" . $charForSnippet, "Lexicon Listing " . $char);
            $page->getMetaInformation()->setMetaDescription($metaDescription);
            $metaKeyword = $this->getSnippet("cbaxLexicon.page.listing.metaKeywords" . $charForSnippet, "Lexicon Listing " . $char);
            $page->getMetaInformation()->setMetaKeywords($metaKeyword);
            $metaTitle = $this->getSnippet("cbaxLexicon.page.listing.metaTitle" . $charForSnippet, $char);
            $page->getMetaInformation()->setMetaTitle($metaTitle);
            $page->getMetaInformation()->setCanonical($linkListing);
        }

        $page = $this->cmsHelper->filterEmptyCmsBlocks($page);

		return $this->renderStorefront('@Storefront/storefront/base.html.twig', [
            'cbaxModulLexicon' => $cbaxModulLexicon,
            'page' => $page,
			'breadcrumbList' => $breadcrumb
        ]);
    }

    /**
     * @Route("/cbax/lexicon/modalInfo/{id?}",
     *     name="frontend.cbax.lexicon.modalInfo",  options={"seo"=true}, methods={"GET"},
     *     defaults={"XmlHttpRequest"=true, "auth_required"=false, "_httpCache"=true, "_routeScope"={"storefront"}})
     */
    public function modalInfo(Request $request, Context $context): Response
    {
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $this->config = $this->config ?? $this->getSystemConfigService()->get(self::CONFIG_PATH, $salesChannelId);

        if (empty($this->config['active'])) return new Response();

        $lexiconEntryId = $request->attributes->get('id');
        if (empty($lexiconEntryId)) return new Response();

        $entry = $this->lexiconHelper->getEntry($context, $lexiconEntryId);
        if (empty($entry)) return new Response();

        $cbaxModulLexicon['entry'] = $entry;
        $cbaxModulLexicon['modalTitle'] = $this->config['headline'];

        $this->lexiconHelper->updateImpressions($context, $cbaxModulLexicon['entry']);

        return $this->renderStorefront('@Storefront/storefront/cbax-lexicon/ajax.html.twig', [
            'cbaxModulLexicon' => $cbaxModulLexicon
        ]);
    }

	/**
	* @Route("/cbax/lexicon/detail/{id?}",
     *     name="frontend.cbax.lexicon.detail",  options={"seo"=true}, methods={"GET"},
     *     defaults={"auth_required"=false, "_httpCache"=true, "_routeScope"={"storefront"}})
	*/
    public function detail(Request $request, Context $context): Response
    {
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $this->config = $this->config ?? $this->getSystemConfigService()->get(self::CONFIG_PATH, $salesChannelId);

        if (empty($this->config['active'])) return $this->forwardToRoute('frontend.home.page');

		$lexiconEntryId = $request->attributes->get('id');
        if (empty($lexiconEntryId)) return $this->forwardToRoute('frontend.cbax.lexicon.index');

        $entry = $this->lexiconHelper->getEntry($context, $lexiconEntryId);
        if (empty($entry)) return $this->forwardToRoute('frontend.cbax.lexicon.index');

        if (!empty($this->config['activeLexicon'])) {
            $shopUrl = $request->attributes->get(RequestTransformer::STOREFRONT_URL);
            $translatedEntry = $entry->getTranslated();
            $translatedEntry['descriptionLong'] = $this->lexiconReplacer->getReplaceText($translatedEntry['descriptionLong'], $salesChannelId, $shopUrl, $context, $salesChannelContext, $this->config, $lexiconEntryId);
            $translatedEntry['description'] = $this->lexiconReplacer->getReplaceText($translatedEntry['description'], $salesChannelId, $shopUrl, $context, $salesChannelContext, $this->config, $lexiconEntryId);
            $entry->setTranslated($translatedEntry);
        }

        $cmsPageId = $this->config['cmsPageDetail'] ?? null;

        // Standard Page Object für Breadcrumb erzeugen
        $newPage = $this->genericPageLoader->load($request, $salesChannelContext);
        $page = LexiconPage::createFrom($newPage);
        //CMS Page
        $cmsPage = $this->cmsHelper->getCmsPage($cmsPageId, $context, 'detail');
        if (empty($cmsPage)) return $this->forwardToRoute('frontend.cbax.lexicon.index');

        if ($cmsPage->getSections())
        {
            $cmsPage->getSections()->sort(function (CmsSectionEntity $a, CmsSectionEntity $b) {
                return $a->getPosition() <=> $b->getPosition();
            });

            //$resolverContext = new ResolverContext($salesChannelContext, $request);
            $resolverContext = new EntityResolverContext($salesChannelContext, $request, $this->lexiconEntryDefinition, $entry);

            // sort blocks into sectionPositions
            foreach ($cmsPage->getSections() as $section) {
                $section->getBlocks()?->sort(function (CmsBlockEntity $a, CmsBlockEntity $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });
            }

            // resolve slot data
            $this->cmsHelper->loadSlotData($cmsPage, $resolverContext);
        }

        $page->setCmsPage($cmsPage);

        $char = $this->lexiconHelper->getCharByEntry($entry);

		// Impressions hochsetzen -> Muss später in Ajax Funktion
		$this->lexiconHelper->updateImpressions($context, $entry);

        $cbaxModulLexicon['page'] = 'detail';
        $cbaxModulLexicon['id'] = $lexiconEntryId;

		$linkIndex = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.index', '/cbax/lexicon/index', $context, $request);
		$linkListing = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.listing' , '/cbax/lexicon/listing/' . $char, $context, $request);
		$linkDetail = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.detail', '/cbax/lexicon/detail/' . $lexiconEntryId, $context, $request);
		$breadcrumbLinkName = $this->container->get('translator')->trans('cbaxLexicon.page.breadcrumb.name');

		$breadcrumb[] = array(
            'link' => $linkIndex,
            'name' => $breadcrumbLinkName,
            'translated' => array(
                'name' => $breadcrumbLinkName
            )
        );
		$breadcrumb[] = array(
            'link' => $linkListing,
            'name' => $char,
			'translated' => array(
				'name' => $char
			)
        );
		$breadcrumb[] = array(
            'link' => $linkDetail,
            'name' => $entry->getTranslated()['title'],
			'active' => true,
			'translated' => array(
				'name' => $entry->getTranslated()['title']
			)
        );

        //Meta
        if ($page->getMetaInformation()) {
            $charForSnippet = str_replace('-', '', $char);
            $page->getMetaInformation()->setCanonical($linkDetail);

            $metaDescription = (string)($entry->getTranslated()['metaDescription'] ?? $entry->getTranslated()['description']);
            $page->getMetaInformation()->setMetaDescription($metaDescription);

            $metaKeyword = (string)($entry->getTranslated()['metaKeywords'] ?? $entry->getTranslated()['keyword']);
            $page->getMetaInformation()->setMetaKeywords($metaKeyword);

            $metaTitle = (string)($entry->getTranslated()['metaTitle'] ?? $entry->getTranslated()['title']);
            //$metaTitle .= ' | ' . $this->getSnippet("cbaxLexicon.page.listing.metaTitle" . $charForSnippet, $char);
            //$metaTitle .= ' | ' . $this->getSnippet("cbaxLexicon.page.index.metaTitle", "Lexicon");
            $page->getMetaInformation()->setMetaTitle($metaTitle);
        }

        $page = $this->cmsHelper->filterEmptyCmsBlocks($page);

		return $this->renderStorefront('@Storefront/storefront/base.html.twig', [
            'cbaxModulLexicon' => $cbaxModulLexicon,
            'page' => $page,
			'breadcrumbList' => $breadcrumb
        ]);
    }

	/**
	* @Route("/cbax/lexicon/content",
     *     name="frontend.cbax.lexicon.content",  options={"seo"=true}, methods={"GET"},
     *     defaults={"auth_required"=false, "_httpCache"=true, "_routeScope"={"storefront"}})
	*/
    public function content(Request $request, Context $context): Response
    {
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $this->config = $this->config ?? $this->getSystemConfigService()->get(self::CONFIG_PATH, $salesChannelId);

        if (empty($this->config['active'])) return $this->forwardToRoute('frontend.home.page');

        $cmsPageId = $this->config['cmsPageContent'] ?? null;
        // Standard Page Object für Breadcrumb erzeugen
        $newPage = $this->genericPageLoader->load($request, $salesChannelContext);
        $page = LexiconPage::createFrom($newPage);

        //CMS Page
        $cmsPage = $this->cmsHelper->getCmsPage($cmsPageId, $context, 'content');
        if (empty($cmsPage)) return $this->forwardToRoute('frontend.cbax.lexicon.index');

        if ($cmsPage->getSections())
        {
            $cmsPage->getSections()->sort(function (CmsSectionEntity $a, CmsSectionEntity $b) {
                return $a->getPosition() <=> $b->getPosition();
            });

            $resolverContext = new ResolverContext($salesChannelContext, $request);

            // sort blocks into sectionPositions
            foreach ($cmsPage->getSections() as $section) {
                $section->getBlocks()?->sort(function (CmsBlockEntity $a, CmsBlockEntity $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });
            }

            // resolve slot data
            $this->cmsHelper->loadSlotData($cmsPage, $resolverContext);
        }

        $page->setCmsPage($cmsPage);

        $cbaxModulLexicon['page'] = 'content';
		$linkIndex = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.index', '/cbax/lexicon/index', $context, $request);
		$link_content = $this->lexiconSeo->getSeoUrl('frontend.cbax.lexicon.content' , '/cbax/lexicon/content', $context, $request);
		$breadcrumbLinkName = $this->container->get('translator')->trans('cbaxLexicon.page.breadcrumb.name');
		$breadcrumbLinkContent = $this->container->get('translator')->trans('cbaxLexicon.page.navigation.linkContent');

		$breadcrumb[] = array(
            'link' => $linkIndex,
            'name' => $breadcrumbLinkName,
            'translated' => array(
                'name' => $breadcrumbLinkName
            )
        );
		$breadcrumb[] = array(
            'link' => $link_content,
            'name' => $breadcrumbLinkContent,
			'active' => true,
			'translated' => array(
				'name' => $breadcrumbLinkContent
			)
        );

        //Meta
        if ($page->getMetaInformation()) {
            $metaDescription = $this->getSnippet("cbaxLexicon.page.content.metaDescription", "Lexicon Content");
            $page->getMetaInformation()->setMetaDescription($metaDescription);
            $metaKeyword = $this->getSnippet("cbaxLexicon.page.content.metaKeywords", "Lexicon Content");
            $page->getMetaInformation()->setMetaKeywords($metaKeyword);
            $metaTitle = $this->getSnippet("cbaxLexicon.page.content.metaTitle", "Table of Contents");
            $page->getMetaInformation()->setMetaTitle($metaTitle);
            $page->getMetaInformation()->setCanonical($link_content);
        }

        $page = $this->cmsHelper->filterEmptyCmsBlocks($page);

		return $this->renderStorefront('@Storefront/storefront/base.html.twig', [
            'cbaxModulLexicon' => $cbaxModulLexicon,
            'page' => $page,
			'breadcrumbList' => $breadcrumb
        ]);
    }

    private function getSnippet(string $snippetName, ?string $fallbackSnippet = null) : string
    {
        $localeSnippet = $this->trans($snippetName);

        if ($localeSnippet === $snippetName && $fallbackSnippet !== null) {
            return $fallbackSnippet;
        }

        return $localeSnippet;
    }
}
