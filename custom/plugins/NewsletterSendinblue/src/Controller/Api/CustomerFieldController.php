<?php

namespace NewsletterSendinblue\Controller\Api;

use NewsletterSendinblue\Model\Field;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\NewsletterSubscriptionServiceInterface;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldEntity;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class CustomerFieldController extends AbstractController
{
    const NEWSLETTER_RECEIVER_STATUS_SUBSCRIBED = 'subscribed';
    const NEWSLETTER_RECEIVER_STATUS_UNSUBSCRIBED = 'unsubscribed';

    private $customFieldSetRepository;
    private $customerRepository;
    
    /**
     * CustomerFieldController constructor.
     * @param DefinitionInstanceRegistry $definitionInstanceRegistry
     * @param CustomFieldSetDefinition $customFieldSetDefinition
     * @param EntityRepository $customerRepository
     */
    public function __construct(DefinitionInstanceRegistry $definitionInstanceRegistry, CustomFieldSetDefinition $customFieldSetDefinition, EntityRepository $customerRepository)
    {
        $this->customFieldSetRepository = $definitionInstanceRegistry->getRepository($customFieldSetDefinition->getEntityName());
        $this->customerRepository = $customerRepository;
    }

    /**
     * @Route("/api/v{version}/sendinblue/customers/fields", name="api.v.action.sendinblue.getCustomerFields", methods={"GET"})
     * @Route("/api/sendinblue/customers/fields", name="api.action.sendinblue.getCustomerFields", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getCustomerFields(Request $request, Context $context): JsonResponse
    {
        $data = [];
        $customerFields = array_merge($this->getCustomerDefaultFields(), $this->_getCustomerCustomFields());
        /** @var Field $field */
        foreach ($customerFields as $field) {
            if ($field->getId() === 'customFields') {
                continue;
            }

            $data[] = [
                Field::FIELD_ID => $field->getId(),
                Field::FIELD_NAME => $field->getName(),
                Field::FIELD_TYPE => $field->getType(),
                Field::FIELD_DESCRIPTION => $field->getDescription()
            ];
        }

        return new JsonResponse(
            [
                'success' => true,
                'data' => $data
            ]
        );
    }

    private function _getCustomerCustomFields()
    {
        $fields = [];
        try {
            //get customer custom fields
            $criteria = new Criteria();
            $criteria->addAssociation('customFields');
            $criteria->addAssociation('relations');
            $result = $this->customFieldSetRepository->search($criteria, Context::createDefaultContext());

            /** @var CustomFieldSetEntity $customFieldSetEntity */
            foreach ($result->getElements() as $customFieldSetEntity) {
                foreach ($customFieldSetEntity->getRelations()->getElements() as $relation) {
                    if ($relation->getEntityName() === 'customer') {
                        /** @var CustomFieldEntity $customField */
                        foreach ($customFieldSetEntity->getCustomFields() as $customField) {
                            $fieldName = $customFieldSetEntity->getName() . '__' . $customField->getName();
                            $translated = $customField->getTranslated();
                            $fieldDescription = !empty($translated) ? reset($translated) : '';
                            $fields[] = new Field(
                                'sib_' . $customField->getName(),
                                DatatypeHelper::convertToSendinblueDatatype($customField->getType()),
                                $fieldName,
                                $fieldDescription
                            );
                        }
                    }
                }
            }

        } catch (\Exception $exception) {
            //
        }

        return $fields;
    }

    /**
     * @param String $customFields
     * @return array
     * @example customFields should be a string with a comma separated values e.g. 'firstName,lastName,phone'
     */
    public function getCustomerEntityFields(string $customFields): array
    {
        $fields = [];

        if (empty($customFields)) {
            $customFields = '[]';
        }

        $customFields = json_decode($customFields, true);

        $allCustomerFields = array_merge($this->getCustomerDefaultFields(), $this->_getCustomerCustomFields());
        if (count($customFields) === 0) {
            return $allCustomerFields;
        }

        /** @var Field $customerField */
        foreach ($allCustomerFields as $customerField) {
            //email and id must always be included
            if ($customerField->getId() === 'id' || $customerField->getId() === 'newsletter') {
                $fields[] = $customerField;
            } elseif (in_array($customerField->getId(), $customFields)) {
                $fields[] = $customerField;
            }
        }

        return $fields;
    }

    private function getCustomerDefaultFields(): array
    {
        return [
            new Field('id'),
            new Field('orderCount', Field::DATATYPE_INTEGER),
            new Field('groupId', Field::DATATYPE_STRING),
            new Field('salutation', Field::DATATYPE_STRING, 'Title'),
            new Field('email'),
            new Field('language'),
            new Field('firstName'),
            new Field('lastName'),
            new Field('guest', Field::DATATYPE_BOOLEAN),
            new Field('newsletter', Field::DATATYPE_BOOLEAN),
            new Field('birthday', Field::DATATYPE_DATE),
            new Field('billingCountry', Field::DATATYPE_STRING),
            new Field('billingCity', Field::DATATYPE_STRING),
            new Field('billingZipcode', Field::DATATYPE_STRING),
            new Field('billingStreet', Field::DATATYPE_STRING),
            new Field('defaultPaymentMethod', Field::DATATYPE_DATE),
            new Field('createdAt', Field::DATATYPE_DATE),
            new Field('updatedAt', Field::DATATYPE_DATE),
            new Field('salesChannelId', Field::DATATYPE_STRING),
            new Field('salesChannelName', Field::DATATYPE_STRING),
            new Field('phone'),
            //new Field('promotions', Field::DATATYPE_ARRAY),
        ];
    }

    public function prepareCustomerAttributes(EntityCollection $customerList, array $fields): array
    {
        $preparedCustomerList = [];
        /**
         * @var String $key
         * @var CustomerEntity $customerEntity
         */
        foreach ($customerList as $key => $customerEntity) {
            /** @var Field $field */
            foreach ($fields as $field) {
                $fieldId = $field->getId();
                if ($fieldId === 'defaultPaymentMethod') {
                    $defaultPaymentMethod = $customerEntity->getDefaultPaymentMethod();
                    if ($defaultPaymentMethod instanceof PaymentMethodEntity) {
                        $preparedCustomerList[$key][$fieldId] = $defaultPaymentMethod->getName();
                    }
                } elseif ($fieldId === 'salesChannelName') {
                    $salesChannel = $customerEntity->getSalesChannel();
                    if ($salesChannel instanceof SalesChannelEntity) {
                        $preparedCustomerList[$key][$fieldId] = $salesChannel->getName();
                    }
                } else {
                    $preparedCustomerList[$key][$fieldId] = $this->getCustomerAttributeValue($customerEntity, $fieldId);
                }
            }
        }

        return $preparedCustomerList;
    }

    private function preparePromotionCollection(PromotionCollection $promotionCollection): ?array
    {
        $promotions = [];

        if ($promotionCollection->count() > 0) {
            /**
             * @var string $promotionKey
             * @var PromotionEntity $promotionEntity
             */
            foreach ($promotionCollection->getElements() as $promotionKey => $promotionEntity) {
                $promotions[$promotionKey]['name'] = $promotionEntity->getName() ?: '';
                $promotions[$promotionKey]['validFrom'] = $promotionEntity->getValidFrom()->format('Y-m-d H:i:s') ?: '';
                $promotions[$promotionKey]['validUntil'] = $promotionEntity->getValidUntil()->format('Y-m-d H:i:s') ?: '';
                $promotions[$promotionKey]['exclusive'] = $promotionEntity->isExclusive() ?: '';
                $promotions[$promotionKey]['code'] = $promotionEntity->getCode() ?: '';
            }
        }

        return $promotions;
    }

    public function prepareNewsletterRecipients(array $newsletterRecipientList, array $fields = []): array
    {
        $preparedList = [];

        /** @var CustomerEntity|NewsletterRecipientEntity $newsletterRecipient */
        foreach ($newsletterRecipientList as $newsletterRecipient) {
            $preparedList[$newsletterRecipient->getId()] = [
                'id' => $newsletterRecipient->getId()
            ];

            if (!empty($fields)) {
                $salesChannel = $newsletterRecipient->getSalesChannel();
                /** @var CustomerEntity|null $customer */
                $customer = null;
                if ($salesChannel) {
                    $customer = $this->getCustomerByEmail($newsletterRecipient->getEmail(), $salesChannel->getId());
                }

                /** @var Field $field */
                foreach ($fields as $field) {
                    $fieldId = $field->getId();
                    if ($fieldId === 'id') {
                        continue;
                    }
                    $recipientId = $newsletterRecipient->getId();

                    switch ($fieldId) {
                        case 'email':
                            $preparedList[$recipientId][$fieldId] = $newsletterRecipient->getEmail() ?: '';
                            break;
                        case 'salutation':
                            $salutationEntity = $customer ? ($customer->getSalutation() ?: null) : ($newsletterRecipient->getSalutation() ?: null);
                            $preparedList[$recipientId][$fieldId] = $salutationEntity ? $salutationEntity->getDisplayName() : '';
                            break;
                        case 'firstName':
                            $preparedList[$recipientId][$fieldId] = $customer ? $customer->getFirstName() :
                                ($newsletterRecipient->getFirstName() ?: '');
                            break;
                        case 'lastName':
                            $preparedList[$recipientId][$fieldId] = $customer ? $customer->getLastName() :
                                ($newsletterRecipient->getLastName() ?: '');
                            break;
                        case 'groupId':
                            if ($newsletterRecipient instanceof CustomerEntity) {
                                $preparedList[$recipientId][$fieldId] = $newsletterRecipient->getGroupId();
                                break;
                            }
                            $preparedList[$recipientId][$fieldId] = GroupController::GROUP_NEWSLETTER_RECIPIENT;
                            break;
                        case 'newsletter':
                            if ($newsletterRecipient instanceof CustomerEntity) {
                                break;
                            }
                            if (interface_exists('Shopware\Core\Content\Newsletter\NewsletterSubscriptionServiceInterface')) {
                                $statuses = [
                                    NewsletterSubscriptionServiceInterface::STATUS_OPT_OUT
                                ];
                            } else {
                                $statuses = [
                                    NewsletterSubscribeRoute::STATUS_OPT_OUT
                                ];
                            }

                            $preparedList[$recipientId][$fieldId] = !in_array($newsletterRecipient->getStatus(), $statuses);
                            break;
                        case 'language':
                            $languageEntity = $customer ? ($customer->getLanguage() ?: null) : ($newsletterRecipient->getLanguage() ?: null);
                            $preparedList[$recipientId][$fieldId] = $languageEntity ? $languageEntity->getName() : '';
                            break;
                        case 'salesChannelId':
                            $newsletterRecipientSAlesChannel = $newsletterRecipient->getSalesChannel();
                            if ($newsletterRecipientSAlesChannel instanceof SalesChannelEntity) {
                                $preparedList[$recipientId][$fieldId] = $newsletterRecipientSAlesChannel->getId() ?: '';
                            }
                            break;
                        case 'salesChannelName':
                            $salesChannel = $newsletterRecipient->getSalesChannel();
                            $preparedList[$recipientId][$fieldId] = $salesChannel ? $salesChannel->getName() : '';
                            break;
                        case 'updatedAt':
                            $updatedAt = $newsletterRecipient->getUpdatedAt();
                            $preparedList[$recipientId][$fieldId] = $updatedAt ? $updatedAt->format('Y-m-d') : '';
                            break;
                        case 'createdAt':
                            $createdAt = $newsletterRecipient->getCreatedAt();
                            $preparedList[$recipientId][$fieldId] = $createdAt ? $createdAt->format('Y-m-d') : '';
                            break;
                        default:
                            if ($customer) {
                                $preparedList[$recipientId][$fieldId] = $this->getCustomerAttributeValue($customer, $fieldId);
                            } else {
                                $preparedList[$recipientId][$fieldId] = '';
                            }
                    }
                }
            }
        }

        return $preparedList;
    }

    private function getCustomerAttributeValue(?CustomerEntity $customer, string $fieldId)
    {
        $value = '';

        if (empty($customer)) {
            return $value;
        }

        $methodName = 'get' . ucfirst($fieldId);
        if (method_exists($customer, $methodName)) {
            $attributeValue = $customer->{$methodName}();
            $value = $this->_getCustomerAttribute($attributeValue);
        } else {

            if (strpos($fieldId, 'sib_') === 0) {
                $value = $this->getCustomFieldValue($customer, $fieldId);
            } else {
                $value = $this->getAddressRelatedFieldValue($customer, $fieldId);
            }
        }

        return $value;
    }

    private function _getCustomerAttribute($attributeValue)
    {

        switch (true) {
            case is_string($attributeValue):
            case is_integer($attributeValue):
            case is_double($attributeValue):
            case is_bool($attributeValue):
                $value = $attributeValue;
                break;
            case $attributeValue instanceof SalutationEntity:
                $value = $attributeValue->getDisplayName();
                break;
            case $attributeValue instanceof \DateTimeInterface:
                $value = $attributeValue->format('Y-m-d');
                break;
            case $attributeValue instanceof Entity:
                $value = $attributeValue->getName();
                break;
            default:
                $value = '';
        }

        return $value;
    }


    private function getAddressRelatedFieldValue(CustomerEntity $customer, string $fieldId): ?string
    {
        $value = '';
        $billingAddress = $customer->getDefaultBillingAddress();

        if (!$billingAddress instanceof CustomerAddressEntity) {
            return $value;
        }

        switch ($fieldId) {
            case 'billingCountry':
                $country = $billingAddress->getCountry();
                $value = $country ? $country->getName() : '';
                break;
            case 'billingZipcode':
                $value = $billingAddress->getZipcode();
                break;
            case 'billingStreet':
                $value = $billingAddress->getStreet();
                break;
            case 'billingCity':
                $value = $billingAddress->getCity();
                break;
            case 'phone':
                $value = $billingAddress->getPhoneNumber();
                break;
        }

        return $value;
    }

    private function getCustomFieldValue(CustomerEntity $customer, string $fieldId)
    {
        $customFields = $customer->getCustomFields();

        return $customFields && isset($customFields[substr($fieldId, 4)]) ?
            $customFields[substr($fieldId, 4)] : '';
    }

    /**
     * @param string $email
     * @param string $salesChannelId
     * @return CustomerEntity|null
     */
    private function getCustomerByEmail(string $email, string $salesChannelId): ?CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customer.email', $email));
        $criteria->addFilter(new EqualsFilter('customer.salesChannelId', $salesChannelId));
        $criteria->addAssociations([
            'salutation',
            'language',
            'defaultPaymentMethod',
            'defaultBillingAddress.country',
            'customFields',
            'orderCustomers'
        ]);

        return $this->customerRepository->search($criteria, Context::createDefaultContext())->first();
    }
}
