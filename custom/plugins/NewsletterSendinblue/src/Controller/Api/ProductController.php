<?php

namespace NewsletterSendinblue\Controller\Api;

use Monolog\Logger;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\Checkout\Exception\CurrencyNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class ProductController extends AbstractController
{
    private const DEFAULT_LIMIT = 10;

    private const DEFAULT_PAGE_NUMBER = 1;

    private $logger;

    private $productFieldController;

    private $productRepository;

    private $currencyRepository;

    /**
     * ProductController constructor.
     * @param ProductFieldController $productFieldController
     */
    public function __construct(Logger $logger, ProductFieldController $productFieldController, EntityRepository $productRepository, EntityRepository $currencyRepository)
    {
        $this->logger = $logger;
        $this->productFieldController = $productFieldController;
        $this->productRepository = $productRepository;
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @Route("/api/v{version}/sendinblue/products", name="api.v.action.sendinblue.getProducts", methods={"GET"})
     * @Route("/api/sendinblue/products", name="api.action.sendinblue.getProducts", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProductsAction(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $term = trim((string)$request->get('term'));
        $limit = $request->get('limit', self::DEFAULT_LIMIT);
        $page = $request->get('page', self::DEFAULT_PAGE_NUMBER);
        $offset = $limit * ($page - 1);

        try {
            $criteria = new Criteria();
            $criteria->addAssociation('media');
            $criteria->addAssociation('cover');
            $criteria->addAssociation('categories');
            $criteria->addFilter(new EqualsFilter('active', 1));
            $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
            $criteria->setTerm($term);
            $criteria->setLimit($limit);
            $criteria->setOffset($offset);

            $productListSearchResult = $this->productRepository->search($criteria, $context);

            if (!$productListSearchResult->getTotal()) {
                $this->logger->addRecord(Logger::INFO, 'No Product Found',['term' => $term]);                
                return new JsonResponse([
                    'success' => true,
                    'result' => [],
                    'count' => 0,
                    'countPerPage' => (int)$limit
                    ]
                );
            }

            $products = [];
            /** @var ProductEntity $product */
            foreach ($productListSearchResult as $product) {
                $products[] = $this->productFieldController->prepareProductAttributes($product);
            }
            
            $response = [
                'success' => true,
                'result' => $products,
                'currency' => $this->getCurrencyIsoCode($context->getCurrencyId(), $context),
                'currencySymbol' => $this->getCurrencySymbol($context->getCurrencyId(), $context),
                'count' => $productListSearchResult->getTotal(),
                'countPerPage' => (int)$limit,
            ];
        } catch (\Exception $exception) {
            $this->logger->addRecord(Logger::ERROR, $exception->getMessage());
            $response = [
                'success' => true,
                'result' => [],
                'count' => 0,
                'countPerPage' => (int)$limit,
                'error' => $exception->getMessage()
            ];
        }

        return new JsonResponse($response);
    }
    /**
     * @Route("/api/v{version}/sendinblue/products/media", name="api.v.action.sendinblue.getProductMedia", methods={"GET"})
     * @Route("/api/sendinblue/products/media", name="api.action.sendinblue.getProductMedia", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProductMediaAction(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $productNumber = $request->get('productNumber');

        if (empty($productNumber)) {
            {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'missing "productNumber" parameter'
                ]);
            }
        }

        try {
            $repository = $this->container->get('product.repository');
            $criteria = new Criteria();
            $criteria->addAssociation('media');
            $criteria->addFilter(new EqualsFilter('active', 1));
            $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
            /** @var ProductEntity $result */
            $result = $repository->search($criteria, $context)->first();
            /** @var MediaCollection $media */
            $media = $result->getMedia();

            $data = [];
            /** @var ProductMediaEntity $mediaEntity */
            foreach ($media->getElements() as $mediaEntity) {
                $data[] = $mediaEntity->getMedia()->getUrl();
            }

            $response['success'] = true;
            $response['data'] = $data;

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
    
    private function getCurrencyIsoCode(string $currencyId, Context $context): string 
    {
        try {
            return $this->getCurrencyEntity($currencyId, $context)->getIsoCode();
        } catch (CurrencyNotFoundException $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
            return '';
        }
    }

    private function getCurrencySymbol(string $currencyId, Context $context): string 
    {
        try {
            return $this->getCurrencyEntity($currencyId, $context)->getSymbol();
        } catch (CurrencyNotFoundException $e) {
            $this->logger->addRecord(Logger::ERROR, $e->getMessage());
            return '';
        }
    }

    /**
     * @throws CurrencyNotFoundException
     */
    private function getCurrencyEntity(string $currencyId, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$currencyId]);

        /** @var CurrencyCollection $currencyCollection */
        $currencyCollection = $this->currencyRepository->search($criteria, $context);

        $currencyEntity = $currencyCollection->get($currencyId);
        if ($currencyEntity === null) {
            throw new CurrencyNotFoundException($currencyId);
        }

        return $currencyEntity;
    }
}
