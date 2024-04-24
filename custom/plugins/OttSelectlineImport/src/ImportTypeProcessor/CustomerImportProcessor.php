<?php declare(strict_types=1);

namespace Ott\SelectlineImport\ImportTypeProcessor;

use Ott\SelectlineImport\Dbal\Entity\ImportMessageEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

final class CustomerImportProcessor extends ImportProcessor implements ImportTypeProcessorInterface
{
    public const PROCESSOR_TYPE = 'customer';
    public const PREPAYMENT_ID = 'ead917a565274cc3871221a7710604c6';
    public const INVOICEPAYMENT_ID = '705bb9301aba447d9a42b04603fb38a3';

    public function import(ImportMessageEntity $message): void
    {
        $workload = $message->getWorkload();

        $this->importService->setDefaultLanguage('Deutsch');

        $customer = new CustomerEntity();
        $customerBilling = new CustomerAddressEntity();

        $customer->setCustomerNumber($workload['Kundennummer']);
        $customer->setFirstName('no');
        $customer->setPassword($this->importExtensionGateway->getCustomerPassword($workload['Kundennummer']));
        $customer->setLastName('data');
        $customer->setEmail(!empty($workload['Email']) ? $workload['Email'] : '');
        $customer->setGuest(false);
        $customer->setActive(true);
        $customer->setCompany(!empty($workload['Firma']) ? $workload['Firma'] : '');
        $customer->setOrderCount(0);
        $customer->setDoubleOptInRegistration(false);

        if (!empty($workload['Kundennummer'])) {
            $customerGroupId = $this->importExtensionGateway->getCustomerGroupId($workload['Kundennummer']);
            if (!empty($customerGroupId)) {
                $customer->setGroupId($customerGroupId);
            }
        }

        $customerBilling->setFirstName('no');
        $customerBilling->setLastName('data');
        $customerBilling->setCompany(!empty($workload['Firma']) ? $workload['Firma'] : '');
        $customerBilling->setStreet(!empty($workload['Strasse']) ? $workload['Strasse'] : '');
        $customerBilling->setZipcode(!empty($workload['PLZ']) ? $workload['PLZ'] : '');
        $customerBilling->setCity(!empty($workload['Ort']) ? $workload['Ort'] : '');

        if (!empty($workload['Zusatz1'])) {
            $this->setIfNotNull($customerBilling, $workload['Zusatz1'], 'setAdditionalAddressLine1');
        }
        if (!empty($workload['Zusatz2'])) {
            $this->setIfNotNull($customerBilling, $workload['Zusatz2'], 'setAdditionalAddressLine2');
        }
        if (!empty($workload['Telefon'])) {
            $this->setIfNotNull($customerBilling, $workload['Telefon'], 'setPhoneNumber');
        }

        $countryId = $this->customerModule->getCountryId(!empty($workload['Land']) ? $workload['Land'] : '');
        $customerBilling->setCountryId($countryId);

        $customer->setDefaultBillingAddress($customerBilling);
        $customer->setActiveBillingAddress($customerBilling);

        $shippingAddressData = $this->importExtensionGateway->getDefaultShippingAddress($workload['Kundennummer']);

        if (null !== $shippingAddressData) {
            $customerShipping = new CustomerAddressEntity();
            $customerShipping->setId($shippingAddressData['id']);
            $customerShipping->setFirstName($shippingAddressData['first_name']);
            $customerShipping->setLastName($shippingAddressData['last_name']);
            $customerShipping->setCompany($shippingAddressData['company'] ?: '');
            $customerShipping->setDepartment($shippingAddressData['department'] ?: '');
            $customerShipping->setStreet($shippingAddressData['street']);
            $customerShipping->setZipcode($shippingAddressData['zipcode'] ?? '');
            $customerShipping->setCity($shippingAddressData['city']);
            $customerShipping->setCountryId($shippingAddressData['country_id']);
            $customerShipping->setSalutationId($shippingAddressData['salutation_id']);
            $customerShipping->setPhoneNumber($shippingAddressData['phone_number'] ?: '');
            $customerShipping->setAdditionalAddressLine1($shippingAddressData['additional_address_line1'] ?: '');
            $customerShipping->setAdditionalAddressLine2($shippingAddressData['additional_address_line2'] ?: '');
            $customer->setDefaultShippingAddress($customerShipping);
        }

        $customerGroupName = 'Pricegroup' . $workload['Preisgruppe'];
        $customer->setCustomFields([
            'custom_pricegroup_text' => $customerGroupName,
        ]);

        $defaultPaymentMethodId = self::INVOICEPAYMENT_ID;
        if (5 === (int) $workload['Zahlungsbedingung']) {
            $defaultPaymentMethodId = self::PREPAYMENT_ID;
        }
        $customer->setDefaultPaymentMethodId($defaultPaymentMethodId);

        $this->importService->importCustomer($customer);
    }

    private function setIfNotNull(Entity $entity, ?string $value, string $setter): void
    {
        if (null !== $value) {
            $entity->{$setter}($value);
        }
    }
}
