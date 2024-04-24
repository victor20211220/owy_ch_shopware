<?php

namespace NewsletterSendinblue\Controller\Api;

use Exception;
use Monolog\Logger;
use NewsletterSendinblue\Service\ConfigService;
use NewsletterSendinblue\Service\VersionProvider;
use NewsletterSendinblue\Traits\HelperTrait;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class SendTestMailController extends AbstractController
{
    use HelperTrait;

    private const SUBJECT = '[Sendinblue SMTP] test email';

    private const SENDER_EMAIL = 'contact@sendinblue.com';

    private const CONTENT_TYPE = 'text/html';

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var EntityRepository
     */
    private $systemConfigRepository;
    /**
     * @var Logger
     */
    private $logger;

    private $messageFactory;

    private $mailSender;

    private $checkComptability;

    public function __construct(
        ConfigService             $configService,
        VersionProvider           $versionProvider,
        EntityRepository $systemConfigRepository,
        Logger                    $logger
    )
    {
        $this->checkComptability = $versionProvider->checkShopwareComptability();
        $this->configService = $configService;
        $this->systemConfigRepository = $systemConfigRepository;
        $this->logger = $logger;
    }

    /**
     * @Route("/api/v{version}/sendinblue/sendtestmail", name="api.v.action.sendinblue.sendTestMail", methods={"POST"}, defaults={"auth_required"=false})
     * @Route("/api/sendinblue/sendtestmail", name="api.action.sendinblue.sendTestMail", methods={"POST"}, defaults={"auth_required"=false})
     */
    public function sendTestMailAction(Request $request, Context $context): JsonResponse
    {
        if ($this->checkComptability) {
            $this->messageFactory = $this->container->get('Shopware\Core\Content\Mail\Service\MailFactory');
            $this->mailSender = $this->container->get('Shopware\Core\Content\Mail\Service\MailSender');
        } else {
            $this->messageFactory = $this->container->get('Shopware\Core\Content\MailTemplate\Service\MessageFactory');
            $this->mailSender = $this->container->get('Shopware\Core\Content\MailTemplate\Service\MailSender');
        }

        $email = $request->get('email');
        $content = $request->get('content');
        if (empty($email) || empty($content)) {
            return $this->getResponse(
                false,
                sprintf('Some of required fields are empty: email=%s, $content=%s', $email, $content),
                550
            );
        }
        $userConnectionId = $request->get('userConnectionId');
        $salesChannelId = $this->getSalesChannelIdByConnectionId($userConnectionId);
        $this->configService->setSalesChannelId($salesChannelId);

        if (!$this->configService->isSmtpEnabled()) {
            return $this->getResponse(false, 'SMTP is disabled in plugin settings', 404);
        }

        if ($this->configService->isShopMailDeliveryDisabled()) {
            return $this->getResponse(
                false,
                'Mail Delivery is disabled in shop settings',
                449
            );
        }

        if ($this->configService->getShopEmailAgent() != ConfigService::CORE_MAILER_AGENT_VALUE) {
            return $this->getResponse(
                false,
                'Sending method is not set to SMTP in shop settings',
                449
            );
        }

        try {
            $result = $this->sendMail($email, $content, $context);
            if (!($result instanceof Email)) {
                return $this->getResponse(
                    false,
                    'Error during sending test e-mail : Message not Created',
                    554
                );
            }

            return $this->getResponse(
                true,
                sprintf('Test e-mail was sent successfully, email address = %s', $email),
                200
            );
        } catch (Exception $e) {
            return $this->getResponse(
                false,
                sprintf('Error during sending test e-mail: %s', $e->getMessage()),
                554
            );
        }
    }

    private function getResponse(bool $isSuccess, string $message, ?int $errorCode = null): JsonResponse
    {
        if (!$isSuccess) {
            $errorMessage = sprintf('Sendinblue test e-mail error: %s', $message);
            $this->logger->addRecord(Logger::ERROR, $errorMessage);
        }

        return new JsonResponse([
            'success' => $isSuccess,
            'code' => $errorCode,
            ($isSuccess ? 'message' : 'error') => $message,
        ]);
    }

    private function sendMail(string $recipientEmail, string $content, Context $context): ?Email
    {
        $message = $this->messageFactory->create(
            self::SUBJECT,
            [self::SENDER_EMAIL => ''],
            [$recipientEmail => ''],
            [self::CONTENT_TYPE => $content],
            [],
            []
        );

        $this->mailSender->send($message);

        return $message;
    }
}
