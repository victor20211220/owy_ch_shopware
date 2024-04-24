<?php

namespace NewsletterSendinblue\Subscriber;

use NewsletterSendinblue\Service\ConfigService;
use NewsletterSendinblue\Service\Customer\CustomerProducer;
use NewsletterSendinblue\Service\NewsletterRecipient\NewsletterRecipientProducer;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent;
use Shopware\Core\Content\Newsletter\Event\NewsletterConfirmEvent;
use Shopware\Core\Content\Newsletter\Event\NewsletterRegisterEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class NewsletterRecipientSubscriber implements EventSubscriberInterface
{
    /**
     * @var NewsletterRecipientProducer
     */
    private $newsletterRecipientProducer;

    /**
     * @var CustomerProducer
     */
    private $customerProducer;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var EntityRepository
     */
    private $mailTemplateRepository;

    /**
     * @var RequestStack;
     */
    private $requestStack;

    /**
     * @param NewsletterRecipientProducer $newsletterRecipientProducer
     * @param CustomerProducer $customerProducer
     * @param ConfigService $configService
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $mailTemplateRepository
     * @param RequestStack $requestStack
     */
    public function __construct(
        NewsletterRecipientProducer $newsletterRecipientProducer,
        CustomerProducer            $customerProducer,
        ConfigService               $configService,
        SystemConfigService         $systemConfigService,
        EntityRepository            $mailTemplateRepository,
        RequestStack                $requestStack
    )
    {
        $this->newsletterRecipientProducer = $newsletterRecipientProducer;
        $this->customerProducer = $customerProducer;
        $this->configService = $configService;
        $this->systemConfigService = $systemConfigService;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            NewsletterConfirmEvent::class => 'onNewsletterConfirmEvent',
            NewsletterRegisterEvent::class => 'onNewsletterRegisterEvent',
            EntityWrittenContainerEvent::class => 'onEntityWrittenContainerEvent',
            MailBeforeValidateEvent::class => 'onMailBeforeValidateEvent',
            CustomerRegisterEvent::class => 'onCustomerRegisterEvent',
        ];
    }

    /**
     * @param CustomerRegisterEvent $event
     */
    public function onCustomerRegisterEvent(CustomerRegisterEvent $event): void
    {
        $this->configService->setSalesChannelId($event->getSalesChannelId());
        if ($this->configService->isFullCustomerSyncEnabled()) {
            $customer = $event->getCustomer();

            $this->customerProducer->confirmContact($customer, $event->getSalesChannelId());
        }
    }

    /**
     * @param NewsletterConfirmEvent $event
     */
    public function onNewsletterConfirmEvent(NewsletterConfirmEvent $event): void
    {
        $this->newsletterRecipientProducer->confirmContact($event->getNewsletterRecipient(), $event->getSalesChannelId());
    }

    /**
     * @param NewsletterRegisterEvent $event
     */
    public function onNewsletterRegisterEvent(NewsletterRegisterEvent $event): void
    {
        $this->configService->setSalesChannelId($event->getSalesChannelId());
        if ($this->configService->getSubscriptionMailing()) {
            $this->newsletterRecipientProducer->confirmContact($event->getNewsletterRecipient(), $event->getSalesChannelId());

            if ($this->requestStack
                && $this->requestStack->getCurrentRequest()
                && $this->requestStack->getCurrentRequest()->hasSession()
                && $this->requestStack->getSession()->has('sbCoreDoubleOptIn')
            ) {
                $session = $this->requestStack->getSession();
                $this->systemConfigService->set('core.newsletter.doubleOptIn', $session->get('sbCoreDoubleOptIn'));
                $session->remove('sbCoreDoubleOptIn');
            }
        }
    }

    /**
     * @param EntityWrittenContainerEvent $event
     */
    public function onEntityWrittenContainerEvent(EntityWrittenContainerEvent $event): void
    {
        foreach ($event->getEvents()->getElements() as $eventElement) {
            if ($eventElement->getName() === 'newsletter_recipient.written') {
                foreach ($eventElement->getWriteResults() as $writeResult) {
                    if ($writeResult->getOperation() === 'update') {
                        $payload = $writeResult->getPayload();
                        $newsletterRecipientId = $writeResult->getPrimaryKey();
                        $salesChannelId = $payload['salesChannelId'] ?? null;

                        if (isset($payload['status']) && $payload['status'] == 'optOut') {
                            $this->newsletterRecipientProducer->unsubscribeContact($newsletterRecipientId, $salesChannelId);
                        } else {
                            $this->newsletterRecipientProducer->updateContact($newsletterRecipientId, $salesChannelId);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param MailBeforeValidateEvent $event
     */
    public function onMailBeforeValidateEvent(MailBeforeValidateEvent $event): void
    {
        $data = $event->getData();
        $templateData = $event->getTemplateData();
        $context = $event->getContext();
        if (!isset($templateData['newsletterRecipient']) || !isset($data['templateId'])) {
            return;
        }
        if (!$this->isNewsletterMailTemplate($data['templateId'], $context)) {
            return;
        }
        $salesChannelId = null;
        if (!empty($data['salesChannelId'])) {
            $salesChannelId = $data['salesChannelId'];
        }
        if (empty($salesChannelId) && method_exists($context->getSource(), 'getSalesChannelId')) {
            $salesChannelId = $context->getSource()->getSalesChannelId();
        }
        if (!empty($salesChannelId)) {
            $this->configService->setSalesChannelId($salesChannelId);
        }
        if (!$this->configService->getSubscriptionMailing()) {
            return;
        }
        $event->stopPropagation();
    }

    /**
     * @param string $templateId
     * @param Context $context
     * @return bool
     */
    private function isNewsletterMailTemplate(string $templateId, Context $context)
    {
        $criteria = new Criteria([$templateId]);
        $criteria->addAssociation('mailTemplateType');
        $mailTemplate = $this->mailTemplateRepository->search($criteria, $context)->first();
        if (empty($mailTemplate)) {
            return false;
        }
        return in_array($mailTemplate->getMailTemplateType()->getTechnicalName(), ['newsletterDoubleOptIn', 'newsletterRegister']);
    }
}
