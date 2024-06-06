<?php declare(strict_types=1);

namespace OwyPhotoExchange\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Media\StorefrontMediaValidatorRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class PostService
{
    private EntityRepository $postRepository;
    private StorefrontMediaUploaderService $mediaUploaderService;

    public function __construct(EntityRepository $postRepository, StorefrontMediaUploaderService $mediaUploaderService)
    {
        $this->postRepository = $postRepository;
        $this->mediaUploaderService = $mediaUploaderService;
    }

    private function uploadImages(FileBag $fileBag, Context $context): array
    {
        $mediaIds = [];
        foreach ($fileBag as $code => $file){

            if($file instanceof UploadedFile){
               $mediaIds[] = $this->mediaUploaderService->upload($file, "owy-photo-exchange", "images", $context);
            }

        }
        return $mediaIds;
    }

    private function updateImages(FileBag $fileBag, Context $context, Request $request): array
    {
        $mediaIds = [];
        for($image = 1; $image <= 5; $image++){
            $imageId = $request->get("picture_{$image}_id", false);
            $newImageFile = $fileBag->get("picture_{$image}");
            if($newImageFile){
                $mediaIds[] = $this->mediaUploaderService->upload($newImageFile, "owy-photo-exchange", "images", $context);
            } elseif ($imageId) {
                $mediaIds[] = $imageId;
            }
        }
        return $mediaIds;
    }



    public function createPost(Context $context, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $images = $this->uploadImages($request->files, $context);
        $this->postRepository->create([
            [
                "categoryId" => $request->get("category"),
                "customerId" => $salesChannelContext->getCustomerId(),
                "headline" => $request->get("headline"),
                "body" => $request->get("body"),
                "images" => $images,
                "status" => "UNAPPROVED", // todo: should not be hardcoded
                "isActive" => true,  // todo: should not be hardcoded
            ]
        ], $context);
    }

    public function getPosts(Context $context, Request $request): EntitySearchResult
    {
        $criteria = new Criteria();
        $this->handlePagination($request, $criteria);
        $criteria->addSorting(new FieldSorting('id', FieldSorting::DESCENDING));
        $criteria->addAssociation("category");
        $criteria->addAssociation("customer");
        $criteria->addFilter(new EqualsFilter('isActive', 1));
        $posts = $this->postRepository->search($criteria, $context);
        return $this->addCreateDate($posts);
    }
    public function searchPosts(Context $context, Request $request): EntitySearchResult
    {
        $criteria = new Criteria();

        $criteria->addFilter(new OrFilter([
            new ContainsFilter("headline", $request->get("query", "")),
            new ContainsFilter("body", $request->get("query", "")),
        ]));
        $criteria->addFilter(new EqualsFilter("categoryId", $request->get("category", null)));
        $criteria->addAssociation("category");
        $criteria->addAssociation("customer");
        $criteria->addFilter(new EqualsFilter('isActive', 1));
        $posts = $this->postRepository->search($criteria, $context);
        return $this->addCreateDate($posts);
    }

    public function addCreateDate($posts){
        $elements = $posts->getElements();
        foreach($elements as $key=> $element){
            $elements[$key]->created_date = $element->createdAt;
        }
        $posts->assign(['elements' => $elements]);
        return $posts;
    }


    public function deletePost(Context $context, Request $request): void
    {
        $this->postRepository->delete([
            [
                'id' => $request->get("id", null)
            ]
        ], $context);
    }

    public function updatePost(Context $context, Request $request): void
    {
        $images = $this->updateImages($request->files, $context, $request);
        $this->postRepository->update([
            [
                "id" => $request->get("id"),
                "categoryId" => $request->get("category"),
                "headline" => $request->get("headline"),
                "body" => $request->get("body"),
                "images" => $images,
//                "status" => "UNAPPROVED", // todo: should not be hardcoded
//                "isActive" => true,  // todo: should not be hardcoded
            ]
        ], $context);
    }

    public function getPost(Context $context, Request $request): EntitySearchResult
    {
        return $this->postRepository->search(new Criteria([$request->get("id")]), $context);
    }


    function repositionArrayElement(array &$array, $key, int $order): array
    {
        if (($a = array_search($key, array_keys($array))) === false) {
            throw new \Exception("The {$key} cannot be found in the given array.");
        }
        $p1 = array_splice($array, $a, 1);
        $p2 = array_splice($array, 0, $order);
        $array = array_merge($p2, $p1, $array);

        return $array;
    }

    private function handlePagination(Request $request, Criteria $criteria): void
    {
        $limit = $this->getLimit($request);

        $page = $this->getPage($request);

        $criteria->setOffset(($page - 1) * $limit);
        $criteria->setLimit($limit);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
    }

    private function getLimit(Request $request): int
    {
        $limit = $request->query->getInt('limit', 0);

        //dd($limit);

        if ($request->isMethod(Request::METHOD_POST)) {
            $limit = $request->request->getInt('limit', $limit);


        }

        $limit = $limit > 0 ? $limit : 10;

        return $limit <= 0 ? 2 : $limit;
    }

    private function getPage(Request $request): int
    {
        $page = $request->query->getInt('p', 1);

        if ($request->isMethod(Request::METHOD_POST)) {
            $page = $request->request->getInt('p', $page);
        }

        return $page <= 0 ? 1 : $page;
    }
}