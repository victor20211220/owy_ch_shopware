<?php
declare(strict_types=1);
namespace OwyChTheme\Storefront\Controller;
use Exception;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoader;
use Symfony\Component\Mailer\MailerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Context;
use Frosh\FroshProductCompare\Page\CompareProductPageLoader;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class TemplateController extends StorefrontController
{
    public $mailService;
    public $connection;
    private  $cmsPageLoader;
    public $systemConfigService;
    public $productRepository;


    public function __construct(AbstractMailService $mailService, Connection $connection, SalesChannelCmsPageLoader $cmsPageLoader, SystemConfigService $systemConfigService, EntityRepository $productRepository)
    {
        $this->mailService = $mailService;
        $this->connection = $connection;
        $this->cmsPageLoader = $cmsPageLoader;
        $this->systemConfigService = $systemConfigService;
        $this->productRepository = $productRepository;
    }

    /**
     *
     * @Route("/owy_inquiry_form/fetchcmspage", name="frontend.action.owy_inquiry_form.fetchcmspage", methods={"POST", "POST"}, defaults={"csrf_protected"=false})
     */
    public function fetchcmspage(Request $request, SalesChannelContext $salesChannelContext)
    {
        $cmsPageId = $request->get('firstCmsPageId');
        if($cmsPageId !== null){
            $criteria = (new Criteria([$cmsPageId]))
                ->addAssociation('sections.backgroundMedia')
                ->addAssociation('sections.blocks.backgroundMedia')
                ->addAssociation('sections.blocks.slots')
                ->addAssociation('media')
                ->addAssociation('cover');
            $cmsPageResult = $this->cmsPageLoader->load(
                $request,
                $criteria,
                $salesChannelContext
            );
            $data =  $this->renderStorefront('@Storefront/storefront/element/owy_cms_section.html.twig', [
                'additionalCmsPage' => $cmsPageResult->getElements(),
            ])->getContent();
            $jsonReturn = new JsonResponse([
                'status' => 200,
                'result' => $data
            ]);
            return $jsonReturn;
        }
    }

    /**
     *
     * @Route("/owy_inquiry_form/send", name="frontend.action.owy_inquiry_form.send", options={"seo"="false"}, methods={"POST", "POST"}, defaults={"csrf_protected"=false, "XmlHttpRequest"=true})
     */
    public function send(Request $request, SalesChannelContext $salesChannelContext)
    {


        $formData =  json_decode($request->getContent(),true);

        $message = $formData['message'];
        $firma = $formData['firma']?: "";
        $name = $formData['name'];
        $vorname = $formData['vorname'];
        $strasse = $formData['strasse'];
        $plz = $formData['plz'];
        $telephone = $formData['telephone'];
        $email = $formData['email'];
        $page_val = $formData['page_val'];

        $contentHtml = '<b>Name:</b> ' . $name .  " " . $vorname .'<br/>
                        <b>Email:</b> '.$email.'<br/>
                        <b>Firma: </b>'.$firma.'<br/>
                        <b>Strasse / Nr.: </b>'.$strasse.'<br/>
                        <b>PLZ / Ort: </b>'.$plz.'<br/>
                        <b>Phone: </b>'.$telephone.'<br/>
                        <b>Message: </b>'.$message;

        $shopName = $salesChannelContext->getSalesChannel()->getName();
        $shopEmail = $this->systemConfigService->get('core.basicInformation.email', $salesChannelContext->getSalesChannel()->getId());
        try {
            $data = new ParameterBag();
            $data->set(
                'recipients',
                [
                    $shopEmail => $shopName
                ]
            );

            $data->set('senderName', $formData['name'] ?: $formData['email']);

            $data->set('contentHtml', $contentHtml);
            $data->set('contentPlain', strip_tags($contentHtml));
            if ($page_val == 1){
                $data->set('subject', 'Support Email');
            }else{
                $data->set('subject', 'Kontakt Us Email');
            }

            $data->set('salesChannelId', $salesChannelContext->getSalesChannel()->getId());

            $this->mailService->send(
                $data->all(),
                $salesChannelContext->getContext(),
                []
            );


            return new Response(
                'E-Mail erfolgreich gesendet',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return new Response(
                'Error: ' . $e->getMessage(),
                406,
                ['bcErrorText'=>'Error: ' . $e->getMessage()]
            );
        }

    }


    /**
     *
     * @Route("/owy_inquiry_form/getwishlist", name="frontend.action.owy_inquiry_form.getwishlist", options={"seo"="false"}, methods={"POST", "POST"}, defaults={"csrf_protected"=false, "XmlHttpRequest"=true})
     */

    public function getwishlist(Request $request, SalesChannelContext $context){
        $locale = $request->get('_locale');
        $getwishlistData = array();
        $products = $this->connection->executeQuery('SELECT LOWER(HEX(wp.product_id)) AS productId FROM `customer_wishlist_product` wp JOIN customer_wishlist cw ON wp.customer_wishlist_id = cw.id WHERE cw.customer_id = UNHEX("'.$request->request->all()['customerId'].'")')->fetchAllAssociative();
        if($products != null){
            $productIds = [];
            foreach($products as $product){
                $productIds[] = $product['productId'];
            }
            $page = $compareProductPageLoader->loadPreview($productIds, $request, $context);
            $productsData = $page->getProducts()->getElements();
                $data =  $this->renderStorefront('@Storefront/storefront/component/compare/wishlist.html.twig', [
                    'getwishlistData' => $productsData,
                    'appUrl' => $_SERVER['APP_URL'],
                ])->getContent();
            return new JsonResponse(['status' => true, 'wishlist' => $data]);


        }else if($request->request->all()['customerId'] != ''){
            $data = '<div class="account-menu">
                        <div class="owy-compare-footer-link">';
            if ($locale == 'en-GB'){
                $data.='<div class="alert-heading h6">Your wishlist is empty</div>                         
                                 <div class="owy-compare-fav-link">
                                        <a class="owy-compare-footer-link" href="/en/wishlist">Wishlist</a>
                                 </div>
                        </div>';
            }else{
                $data.='<div class="alert-heading h6">Ihr Merkzettel ist leer</div>
                             <div class="owy-compare-fav-link">
                                    <a class="owy-compare-footer-link" href="/wishlist">Merkliste</a>
                             </div>                                        
                        </div>';
            }
            $data.='<div class="wishlist-user-notsign-note">
                            <div class="wishlist-user-notsign-note-icon">
                                <i class="fa-regular fa-circle-xmark"></i>
                            </div>
                        </div>
                    </div>';
            return new JsonResponse(['status' => false, 'wishlist' => $data]);
        }else{
            return new JsonResponse(['status' => false, 'wishlist' => null]);
        }
    }



}