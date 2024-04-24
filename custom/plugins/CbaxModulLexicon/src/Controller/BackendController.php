<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\Framework\Context;

use Cbax\ModulLexicon\Components\LexiconSeo;
use Cbax\ModulLexicon\Components\LexiconSitemap;
use Cbax\ModulLexicon\Components\LexiconHelper;

class BackendController extends AbstractController
{
    public function __construct(
        private readonly LexiconSeo $lexiconSeo,
        private readonly LexiconSitemap $lexiconSitemap,
        private readonly LexiconHelper $lexiconHelper,
        protected readonly CacheClearer $cacheClearer
    ) {

    }

	/**
     * @Route("/api/cbax/lexicon/seo",
     *     name="api.cbax.lexicon.seo",  methods={"GET"},
     *     defaults={"auth_required"=false, "_routeScope"={"administration"}})
    */
    public function createSeoUrls(Request $request, Context $context): JsonResponse
    {
        $this->cacheClearer->clear();

        $adminLocalLanguage = trim($request->query->get('adminLocaleLanguage',''));

        $result = $this->lexiconSeo->generateSeoUrls($context, $adminLocalLanguage);

        $this->lexiconSitemap->generateSitemap($context);

        return new JsonResponse($result);
    }

    /**
     * @Route("/api/cbax/lexicon/seoDelete",
     *     name="api.cbax.lexicon.seoDelete",  methods={"GET"},
     *     defaults={"auth_required"=false, "_routeScope"={"administration"}})
     */
    public function deleteSeoUrls(): JsonResponse
    {
        $result = $this->lexiconSeo->deleteSeoUrls();

        return new JsonResponse($result);
    }

    /**
     * @Route("/api/cbax/lexicon/saveEntry",
     *     name="api.cbax.lexicon.saveEntry",  methods={"POST"},
     *     defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function saveEntry(Request $request, Context $context): JsonResponse
    {
        $entry = $request->request->all('entry');

        $languageId = trim($request->request->get('languageId',''));

        $result = $this->lexiconHelper->saveEntry($entry, $languageId, $context);

        return new JsonResponse($result);
    }

    /**
     * @Route("/api/cbax/lexicon/getProductCountList",
     *     name="api.cbax.lexicon.getProductCountList",  methods={"GET"},
     *     defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductCountList(): JsonResponse
    {
        return new JsonResponse($this->lexiconHelper->getProductCountList());
    }

    /**
     * @Route("/api/cbax/lexicon/getProductCountStream",
     *     name="api.cbax.lexicon.getProductCountStream",  methods={"POST"},
     *     defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductCountStream(Request $request, Context $context): JsonResponse
    {
        $prodStreamEntries = $request->request->all()['prodStreamEntries'] ?? [];

        return new JsonResponse($this->lexiconHelper->getProductCountStream($prodStreamEntries, $context));
    }

    /**
     * @Route("/api/cbax/lexicon/changeLexiconProducts",
     *     name="api.cbax.lexicon.changeLexiconProducts",  methods={"POST"},
     *     defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function changeLexiconProducts(Request $request): JsonResponse
    {
        $lexiconEntryId = $request->request->get('lexiconEntryId', '');
        $productId = $request->request->get('productId', '');
        $mode = $request->request->get('mode', '');

        return new JsonResponse($this->lexiconHelper->changeLexiconProducts($lexiconEntryId, $productId, $mode));
    }

    /**
     * @Route("/api/cbax/lexicon/getLexiconProducts",
     *     name="api.cbax.lexicon.getLexiconProducts",  methods={"POST"},
     *     defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getLexiconProducts(Request $request): JsonResponse
    {
        $productId = $request->request->get('productId', '');

        return new JsonResponse($this->lexiconHelper->getLexiconProductsEntries($productId));
    }
}
