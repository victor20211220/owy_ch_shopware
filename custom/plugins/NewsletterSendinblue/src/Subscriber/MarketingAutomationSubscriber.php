<?php

namespace NewsletterSendinblue\Subscriber;

use NewsletterSendinblue\Service\ConfigService;
use NewsletterSendinblue\Service\SIBCookieProviderService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MarketingAutomationSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * MarketingAutomationSubscriber constructor.
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => ['onStorefrontRenderEvent', 1]
        ];
    }

    /**
     * @param StorefrontRenderEvent $event
     */
    public function onStorefrontRenderEvent(StorefrontRenderEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if (method_exists($salesChannelContext, 'getSalesChannelId')) {
            $salesChannelId = $salesChannelContext->getSalesChannelId();
        } else {
            $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        }
        $this->configService->setSalesChannelId($salesChannelId);

        $cookiesAllowed = $event->getRequest()->cookies->get(SIBCookieProviderService::SIB_COOKIE_NAME);

        $isPageTrackingEnabled = $cookiesAllowed && $this->configService->isPageTrackingEnabled();

        $event->setParameter('sendinblueIsPageTrackingEnabled', $isPageTrackingEnabled);

        if ($isPageTrackingEnabled) {
            $event->setParameter('sendinblueMAKey', $this->configService->getMAKey());

            if ($customer = $event->getSalesChannelContext()->getCustomer()) {
                $event->setParameter('sendinblueUserEmail', $customer->getEmail());
            }
        }
    }
}
