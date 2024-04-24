<?php declare(strict_types=1);



namespace Frosh\FroshProductCompare\Controller;



use Frosh\FroshProductCompare\Page\CompareProductPageLoader;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

use Shopware\Storefront\Controller\StorefrontController;

use Shopware\Storefront\Page\GenericPageLoaderInterface;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\JsonResponse;



#[Route(defaults: ['_routeScope' => ['storefront']])]

class CompareProductController extends StorefrontController

{

    public function __construct(

        private readonly CompareProductPageLoader $compareProductPageLoader,

        private readonly GenericPageLoaderInterface $genericPageLoader

    ) {

    }



#[Route(path: '/compare', name: 'frontend.compare.page', options: ['seo' => false], defaults: ['_httpCache' => false], methods: ['GET'])]

public function comparePage(Request $request, SalesChannelContext $context): Response

{

    $page = $this->genericPageLoader->load($request, $context);



    return $this->renderStorefront('@FroshProductCompare/storefront/page/compare.html.twig', compact('page'));

}



#[Route(path: '/compare/content', name: 'frontend.compare.content', options: ['seo' => false], defaults: ['_httpCache' => false, 'XmlHttpRequest' => true], methods: ['POST'])]

public function comparePageContent(Request $request, SalesChannelContext $context): Response

{

    $productIds = $request->get('productIds', []);



    $page = $this->compareProductPageLoader->load($productIds, $request, $context);




    return $this->renderStorefront('@FroshProductCompare/storefront/component/compare/content.html.twig', ['page' => $page]);

}



#[Route(path: '/compare/offcanvas', name: 'frontend.compare.offcanvas', options: ['seo' => false], defaults: ['_httpCache' => false, 'XmlHttpRequest' => true], methods: ['POST'])]

public function offcanvas(Request $request, SalesChannelContext $context): Response
{

    $productIds =  json_decode($request->getContent(),true);
    $page = $this->compareProductPageLoader->loadPreview($productIds['productIds'], $request, $context);
    $productsData = $page->getProducts()->getElements();
    
    $data =  $this->renderStorefront('@Storefront/storefront/layout/header/owy_compare.html.twig', [
        'additionalCmsPage' => $productsData,
    ])->getContent();





    $jsonReturn = new JsonResponse([
        'status' => 200,
        'result' => $data,
        'productCount' => count($productsData)
    ]);
    return $jsonReturn;


}

}

