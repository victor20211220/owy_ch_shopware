<?php declare(strict_types=1);
namespace OwyPhotoExchange\Storefront\Controller;


use Shopware\Storefront\Page\GenericPageLoader;
use OwyPhotoExchange\Service\CategoryService;
use OwyPhotoExchange\Service\PostService;
use OwyPhotoExchange\Service\StorefrontMediaUploaderService;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class PhotoExchangeController extends StorefrontController
{

    private CategoryService $categoryService;
    private PostService $postService;
    private StorefrontMediaUploaderService $mediaUploaderService;
    private $genericPageLoader;

    public function __construct(CategoryService $categoryService, PostService $postService, StorefrontMediaUploaderService $mediaUploaderService, GenericPageLoader $genericPageLoader)
    {
        $this->categoryService = $categoryService;
        $this->postService = $postService;
        $this->mediaUploaderService = $mediaUploaderService;
        $this->genericPageLoader = $genericPageLoader;
    }


    /**
     * @Route("/photo-exchange/create", name="frontend.owy.px.create", methods={"GET"}, defaults={"_loginRequired"=true, "_loginRequiredAllowGuest"=true})
     */
    public function create(SalesChannelContext $context, Request $request): Response
    {
        $page = $this->genericPageLoader->load($request, $context);
        $appUrl = $_SERVER['APP_URL'];
        $categories = $this->categoryService->getActiveCategories(Context::createDefaultContext());
        return $this->renderStorefront('@OwyPhotoExchange/storefront/page/create.html.twig', [
            'categories' => $categories,
            'appUrl' => $appUrl,
            'page' => $page,

        ]);
    }

    /**
     * @Route("/photo-exchange/createPost", name="frontend.owy.px.createPost", methods={"POST"})
     */
    public function createPost(Context $context, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if($request->get("id", false)){
            return $this->editPost($context, $request);
        }

        $this->postService->createPost($context, $request, $salesChannelContext);
        return $this->redirectToRoute("frontend.owy.px.list");
    }

    /**
     * @Route("/photo-exchange", name="frontend.owy.px.main", methods={"GET"})
     */
    public function main(Context $context, Request $request): Response
    {
        return $this->list($context,$request);
    }

    /**
     * @Route("/photo-exchange/list", name="frontend.owy.px.list", methods={"GET"},   defaults={"XmlHttpRequest"=true})
     */
    public function list(SalesChannelContext $context,Request $request): Response
    {
        $page = $this->genericPageLoader->load($request, $context);

        $appUrl = $_SERVER['APP_URL'];
        $posts = $this->postService->getPosts(Context::createDefaultContext(), $request);

        return $this->renderStorefront('@OwyPhotoExchange/storefront/page/list.html.twig', [
            "posts" => $posts,
            "appUrl" => $appUrl,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/photo-exchange/search", name="frontend.owy.px.search", methods={"GET"})
     */
    public function search(SalesChannelContext $context, Request $request): Response
    {
        $page = $this->genericPageLoader->load($request, $context);
        $categories = $this->categoryService->getActiveCategories(Context::createDefaultContext());
        $posts = $this->postService->searchPosts(Context::createDefaultContext(), $request);


        return $this->renderStorefront('@OwyPhotoExchange/storefront/page/search.html.twig', [
            'categories' => $categories,
            "posts" => $posts,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/photo-exchange/delete", name="frontend.owy.px.delete", methods={"GET"})
     */
    public function delete(Context $context, Request $request): Response
    {
        $this->postService->deletePost($context, $request);
        return $this->redirectToRoute("frontend.owy.px.list");
    }

    /**
     * @Route("/photo-exchange/edit", name="frontend.owy.px.edit", methods={"GET"})
     */
    public function edit(SalesChannelContext $context, Request $request): Response
    {
        $page = $this->genericPageLoader->load($request, $context);
        $appUrl = $_SERVER['APP_URL'];
        $categories = $this->categoryService->getActiveCategories(Context::createDefaultContext());
        $post = $this->postService->getPost(Context::createDefaultContext(), $request)->first();
        return $this->renderStorefront('@OwyPhotoExchange/storefront/page/create.html.twig', [
            'categories' => $categories,
            'post' => $post,
            'appUrl' => $appUrl,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/photo-exchange/update", name="frontend.owy.px.update", methods={"POST"})
     */
    public function editPost(Context $context, Request $request): Response
    {
        $this->postService->updatePost($context, $request);
        return $this->redirectToRoute("frontend.owy.px.list");
    }


}