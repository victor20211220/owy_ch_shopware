<?php declare(strict_types=1);

namespace NewsletterSendinblue\Content\Newsletter\SalesChannel;

use NewsletterSendinblue\Service\ConfigService;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterSubscribeRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class NewsletterSubscribeRoute extends AbstractNewsletterSubscribeRoute
{

    /**
     * @var AbstractNewsletterSubscribeRoute
     */
    private $inner;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var RequestStack;
     */
    private $requestStack;

    /**
     * @param AbstractNewsletterSubscribeRoute $inner
     * @param ConfigService $configService
     * @param SystemConfigService $systemConfigService
     * @param RequestStack $requestStack
     */
    public function __construct(
        AbstractNewsletterSubscribeRoute $inner,
        ConfigService                    $configService,
        SystemConfigService              $systemConfigService,
        RequestStack                     $requestStack
    )
    {
        $this->inner = $inner;
        $this->configService = $configService;
        $this->systemConfigService = $systemConfigService;
        $this->requestStack = $requestStack;
    }

    /**
     * @return AbstractNewsletterSubscribeRoute
     */
    public function getDecorated(): AbstractNewsletterSubscribeRoute
    {
        return $this->inner;
    }

    /**
     * @Route("/store-api/newsletter/subscribe", name="store-api.newsletter.subscribe", methods={"POST"})
     *
     * @param RequestDataBag $dataBag
     * @param SalesChannelContext $context
     * @param bool $validateStorefrontUrl
     * @return NoContentResponse
     */
    public function subscribe(RequestDataBag $dataBag, SalesChannelContext $context, bool $validateStorefrontUrl = true): NoContentResponse
    {
        $this->configService->setSalesChannelId($context->getSalesChannelId());
        if ($this->configService->getSubscriptionMailing()) {

            if ($this->requestStack
                && $this->requestStack->getCurrentRequest()
                && $this->requestStack->getCurrentRequest()->hasSession()
                && !$this->systemConfigService->get('core.newsletter.doubleOptIn')
            ) {
                $this->requestStack->getSession()->set('sbCoreDoubleOptIn', $this->systemConfigService->get('core.newsletter.doubleOptIn'));
                $this->systemConfigService->set('core.newsletter.doubleOptIn', true);
            }

            $option = $this->configService->getSubscriptionMailingType() === ConfigService::CONFIG_SUBSCRIPTION_MAILING_TYPE_SIMPLE ? 'direct' : 'subscribe';
            $dataBag->set('option', $option);
        }

        return $this->inner->subscribe($dataBag, $context, $validateStorefrontUrl);
    }
}
