<?php

namespace NewsletterSendinblue\Controller\Api;

use NewsletterSendinblue\Service\Customer\AllCustomerService;
use NewsletterSendinblue\Traits\HelperTrait;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Newsletter\NewsletterSubscriptionServiceInterface;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route(defaults: ['_routeScope' => ['api']])]
class CustomerController extends AbstractController
{
    use HelperTrait;

    private const RESPONSE_SUCCESS_CODE = 200;

    private const RESPONSE_ERROR_CODE = 400;

    private const RESPONSE_NOT_FOUND_CODE = 404;

    private const PREDEFINED_FIELDS = [
        'BIRTHDAY' => 'birthday',
        'EMAIL' => 'email',
        'GENDER' => 'salutation',
        'VORNAME' => 'firstName',
        'NACHNAME' => 'lastName',
        'PHONE' => 'phone',
        'SUBSCRIBED' => 'newsletter'
    ];

    /**
     * @var CustomerFieldController
     */
    private $customerFieldController;

    /**
     * @var EntityRepository
     */
    private $systemConfigRepository;

    /**
     * @var AllCustomerService
     */
    private $allCustomerService;

    /**
     * @param CustomerFieldController $customerFieldController
     * @param EntityRepository $systemConfigRepository
     * @param AllCustomerService $allCustomerService
     */
    public function __construct(
        CustomerFieldController $customerFieldController,
        EntityRepository        $systemConfigRepository,
        AllCustomerService      $allCustomerService
    )
    {
        $this->customerFieldController = $customerFieldController;
        $this->systemConfigRepository = $systemConfigRepository;
        $this->allCustomerService = $allCustomerService;
    }

    /**
     * @Route("/api/v{version}/sendinblue/customers/count", name="api.v.action.sendinblue.getCustomers.count", methods={"GET"})
     * @Route("/api/sendinblue/customers/count", name="api.action.sendinblue.getCustomers.count", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getCustomersCount(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $onlySubscribed = $request->get('subscribed');
        $groupId = $request->get('group');
        $userConnectionId = $request->get('userConnectionId');
        $salesChannelId = $this->getSalesChannelIdByConnectionId($userConnectionId);
        /** @var EntityRepository $customerRepository */
        $customerRepository = $this->container->get('customer.repository');

        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('customer.active', 1));

            if ($onlySubscribed) {
                $criteria->addFilter(new EqualsFilter('customer.newsletter', 1));
            }

            if (!empty($salesChannelId)) {
                $criteria->addFilter(new EqualsFilter('customer.salesChannelId', $salesChannelId));
            }

            if ($groupId) {

                if ($groupId === GroupController::GROUP_NEWSLETTER_RECIPIENT) {
                    $response['success'] = true;
                    $response['count'] = count($this->getPreparedNewsletterReceiver($onlySubscribed, $salesChannelId));

                    return new JsonResponse($response);
                }

                $criteria->addFilter(new EqualsFilter('customer.groupId', $groupId));
            }

            $response['success'] = true;
            $response['count'] = $customerRepository->search($criteria, $context)->count();
        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/api/v{version}/sendinblue/subscribers", name="api.v.action.sendinblue.getAllCustomers", methods={"GET"})
     * @Route("/api/sendinblue/subscribers", name="api.action.sendinblue.getAllCustomers", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     * @throws Throwable
     */
    public function getAllCustomersAction(Request $request, Context $context): JsonResponse
    {
        $subscribed = $request->get('subscribed');
        if (is_null($subscribed)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'There is no \'subscribed\' parameter in get request.'
            ]);
        }

        $groupId = $request->get('group', false);
        $offset = $request->get('offset', false);
        $limit = $request->get('limit', 1000);
        $userConnectionId = $request->get('userConnectionId');
        $salesChannelId = $this->getSalesChannelIdByConnectionId($userConnectionId);
        $fields = $this->customerFieldController->getCustomerEntityFields(
            $request->get('fields', json_encode(self::PREDEFINED_FIELDS))
        );

        try {
            // GET CUSTOMERS
            $customers = $this->allCustomerService->getAllCustomers([
                'subscribed' => $subscribed,
                'limit' => $limit,
                'offset' => $offset,
                'salesChannelId' => $salesChannelId,
                'groupId' => $groupId
            ], $context);
            $preparedList = $this->customerFieldController->prepareCustomerAttributes($customers, $fields);

            if ($subscribed || $groupId === GroupController::GROUP_NEWSLETTER_RECIPIENT) {
                $newsletterReceiverData = $this->getPreparedNewsletterReceiver(1, $salesChannelId, $offset, $limit, [], $fields);
                $preparedList = array_merge($newsletterReceiverData, $preparedList);
            }

            $response['success'] = true;
            $response['data'] = $preparedList;
        } catch (Throwable $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/api/v{version}/sendinblue/customers", name="api.v.action.sendinblue.getCustomers", methods={"GET"})
     * @Route("/api/sendinblue/customers", name="api.action.sendinblue.getCustomers", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getCustomersAction(Request $request, Context $context): JsonResponse
    {
        $onlySubscribed = $request->get('subscribed', 'true');
        $offset = $request->get('offset', false);
        $limit = $request->get('limit', 1000);
        $groupId = $request->get('group', false);
        $emails = json_decode($request->get('emails', '[]'), true);
        $fields = $this->customerFieldController->getCustomerEntityFields($request->get('fields', '[]'));
        $userConnectionId = $request->get('userConnectionId');
        $salesChannelId = $this->getSalesChannelIdByConnectionId($userConnectionId);

        try {

            $criteria = new Criteria();

            $criteria->addFilter(new EqualsFilter('customer.active', 1));

            if ($onlySubscribed == 'true' || $onlySubscribed == 1) {
                $criteria->addFilter(new EqualsFilter('customer.newsletter', 1));
            }

            if (!empty($salesChannelId)) {
                $criteria->addFilter(new EqualsFilter('customer.salesChannelId', $salesChannelId));
            }

            if ($offset) {
                if (!is_numeric($offset)) {
                    $offset = (int)$offset;
                }
                $criteria->setOffset($offset);
            }

            if (!is_numeric($limit)) {
                $limit = (int)$limit;
            }

            $criteria->setLimit($limit);

            if ($groupId) {

                if ($groupId === GroupController::GROUP_NEWSLETTER_RECIPIENT) {
                    $preparedNewsletterReceiver = $this->getPreparedNewsletterReceiver($onlySubscribed, $salesChannelId, $offset, $limit, $emails, $fields);

                    $response['success'] = true;
                    $response['data'] = $preparedNewsletterReceiver;

                    return new JsonResponse($response);

                } else {
                    $groupFilter = new EqualsFilter('customer.groupId', $groupId);
                    $criteria->addFilter($groupFilter);
                }
            }

            /** @var EntityRepository $customerRepository */
            $customerRepository = $this->container->get('customer.repository');

            if (!empty($emails)) {
                $criteria->addFilter(new EqualsAnyFilter('customer.email', $emails));
            }
            $criteria->addAssociations([
                'salutation',
                'language',
                'defaultPaymentMethod',
                'defaultBillingAddress',
                'defaultBillingAddress.country',
                'customFields',
                'salesChannel.orders'
            ]);

            $result = $customerRepository->search($criteria, $context)->getEntities();
            $preparedList = $this->customerFieldController->prepareCustomerAttributes($result, $fields);
            $response['success'] = true;
            $response['data'] = $preparedList;

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/api/v{version}/sendinblue/customers/subscribe", name="api.v.action.sendinblue.subscribeCustomer", methods={"POST"})
     * @Route("/api/sendinblue/customers/subscribe", name="api.action.sendinblue.subscribeCustomer", methods={"POST"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function subscribeCustomerAction(Request $request, Context $context): JsonResponse
    {
        $email = $request->get('email');
        $userConnectionId = $request->get('userConnectionId');
        $salesChannelId = $this->getSalesChannelIdByConnectionId($userConnectionId);

        if ($email && $request->get('Subscribe')) {
            $updateResponse = $this->_updateCustomer($email, true, $salesChannelId, $context);
            $response = $updateResponse['response'];
            $code = $updateResponse['code'];
        } else {
            $code = self::RESPONSE_ERROR_CODE;
            $response = ['error' => 'parameter `Subscribed` and `email` can not be empty'];
        }

        return new JsonResponse($response, $code);
    }

    /**
     * @Route("/api/v{version}/sendinblue/customers/unsubscribe", name="api.v.action.sendinblue.unsubscribeCustomer", methods={"POST"})
     * @Route("/api/sendinblue/customers/unsubscribe", name="api.action.sendinblue.unsubscribeCustomer", methods={"POST"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function unsubscribeCustomerAction(Request $request, Context $context): JsonResponse
    {
        $email = $request->get('email');
        $userConnectionId = $request->get('userConnectionId');
        $salesChannelId = $this->getSalesChannelIdByConnectionId($userConnectionId);

        if ($email && $request->get('Unsubscribe')) {
            $updateResponse = $this->_updateCustomer($email, false, $salesChannelId, $context);
            $response = $updateResponse['response'];
            $code = $updateResponse['code'];
        } else {
            $code = self::RESPONSE_ERROR_CODE;
            $response = ['error' => 'parameter `Unsubscribed` and `email` can not be empty'];
        }

        return new JsonResponse($response, $code);
    }

    private function getPreparedNewsletterReceiver(
        $onlySubscribed,
        $salesChannelId = null,
        $offset = null,
        $limit = null,
        $emails = [],
        $fields = null
    )
    {
        /** @var EntityRepository $newsletterRecipientRepository */
        $newsletterRecipientRepository = $this->container->get('newsletter_recipient.repository');
        $criteria = new Criteria();

        if ($offset && is_numeric($offset)) {
            $criteria->setOffset($offset);
        }

        if ($limit && is_numeric($limit)) {
            $criteria->setLimit($limit);
        }

        if ($onlySubscribed) {
            if (interface_exists('Shopware\Core\Content\Newsletter\NewsletterSubscriptionServiceInterface')) {
                $criteria->addFilter(new EqualsAnyFilter('status', [
                    NewsletterSubscriptionServiceInterface::MAIL_TYPE_OPT_IN,
                    NewsletterSubscriptionServiceInterface::STATUS_DIRECT,
                    NewsletterSubscriptionServiceInterface::MAIL_TYPE_REGISTER,
                    NewsletterSubscriptionServiceInterface::STATUS_OPT_IN
                ]));
            } else {
                $criteria->addFilter(new EqualsAnyFilter('status', [
                    NewsletterSubscribeRoute::STATUS_OPT_IN,
                    NewsletterSubscribeRoute::STATUS_DIRECT
                ]));
            }
        }

        if (!empty($emails)) {
            $criteria->addFilter(new EqualsAnyFilter('email', $emails));
        }

        if (!empty($salesChannelId)) {
            $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        }

        $criteria->addAssociations([
            'salesChannel',
            'language',
            'salutation',
            'salesChannel.customerGroup'
        ]);

        $list = $newsletterRecipientRepository->search($criteria, Context::createDefaultContext())->getElements();
        return $this->customerFieldController->prepareNewsletterRecipients($list, $fields);
    }

    /**
     * @param $email
     * @param $newsletter
     * @param $salesChannelId
     * @param Context $context
     * @return array
     */
    private function _updateCustomer($email, $newsletter, $salesChannelId, Context $context): array
    {
        $statusCode = self::RESPONSE_ERROR_CODE;
        $response = [];

        try {
            /** @var EntityRepository $customerRepository */
            $customerRepository = $this->container->get('customer.repository');
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('email', $email));
            if (!empty($salesChannelId)) {
                $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
            }
            /** @var CustomerEntity $customer */
            $customer = $customerRepository->search($criteria, $context)->first();

            if ($customer) {
                $customerRepository->upsert([
                    [
                        'id' => $customer->getId(),
                        'newsletter' => $newsletter
                    ]
                ],
                    $context
                );

                $statusCode = self::RESPONSE_SUCCESS_CODE;
                $response['success'] = true;
            }

            $newsletterUpdateResponse = $this->updateNewsletterReceiver($email, $newsletter, $salesChannelId, $context);

            if ($newsletterUpdateResponse['code'] !== self::RESPONSE_SUCCESS_CODE) {
                $response = $newsletterUpdateResponse['response'];
                $statusCode = $newsletterUpdateResponse['code'];
            }

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return ['response' => $response, 'code' => $statusCode];
    }

    /**
     * @param $email
     * @param $status
     * @param $salesChannelId
     * @param Context $context
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function updateNewsletterReceiver($email, $status, $salesChannelId, Context $context): array
    {
        $statusCode = self::RESPONSE_NOT_FOUND_CODE;
        $response = [];

        if (interface_exists('Shopware\Core\Content\Newsletter\NewsletterSubscriptionServiceInterface')) {
            $status = $status
                ? NewsletterSubscriptionServiceInterface::STATUS_DIRECT
                : NewsletterSubscriptionServiceInterface::STATUS_OPT_OUT;
        } else {
            $status = $status
                ? NewsletterSubscribeRoute::STATUS_DIRECT
                : NewsletterSubscribeRoute::STATUS_OPT_OUT;
        }

        /** @var EntityRepository $newsletterReceiver */
        $newsletterReceiver = $this->container->get('newsletter_recipient.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        if (!empty($salesChannelId)) {
            $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        }
        /** @var CustomerEntity $customer */
        $customer = $newsletterReceiver->search($criteria, $context)->first();
        if ($customer) {
            $newsletterReceiver->upsert([
                [
                    'id' => $customer->getId(),
                    'status' => $status
                ]
            ],
                $context
            );

            $statusCode = self::RESPONSE_SUCCESS_CODE;
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['error'] = 'There is no such recipient.';
        }

        return ['response' => $response, 'code' => $statusCode];
    }
}
