<?php declare(strict_types=1);

namespace Acris\StoreLocator\Subscriber;

use Acris\StoreLocator\Components\StoreLocatorService;
use Acris\StoreLocator\Components\Struct\StoreLocatorConfigStruct;
use Acris\StoreLocator\Custom\StoreLocatorCollection;
use Acris\StoreLocator\Storefront\Controller\StoreLocatorController;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedEvent;
use Shopware\Storefront\Page\Account\Overview\AccountOverviewPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfirmPageSubscriber implements EventSubscriberInterface
{
    public const STORE_LOCATOR_CONFIG_EXTENSION = 'acrisStoreLocatorConfig';
    public const STORE_LOCATOR_STORES_EXTENSION = 'acrisStoreLocatorStores';
    public const STORE_LOCATOR_STORE_SELECTION_KEY = 'acrisStoreLocatorStore';
    const DEFAULT_VALUE_NO_SELECT = 'noSelect';

    private SystemConfigService $configService;
    private StoreLocatorService $storeLocatorService;

    public function __construct(
        SystemConfigService $configService,
        StoreLocatorService $storeLocatorService
    )
    {
        $this->configService = $configService;
        $this->storeLocatorService = $storeLocatorService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onConfirmPageLoaded',
            CheckoutOrderPlacedEvent::class => 'onCheckoutOrderPlaced',
            AccountOverviewPageLoadedEvent::class => 'onAccountOverviewPageLoaded',
            AccountLoginPageLoadedEvent::class => 'onAccountLoginPageLoaded',
            CustomerEvents::MAPPING_REGISTER_CUSTOMER => 'onRegisterCustomerEvent'
        ];
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        $checkoutSelection = $this->configService->get('AcrisStoreLocatorCS.config.checkoutSelection', $salesChannelId);
        if ($checkoutSelection !== 'singleSelect') {
            return;
        }

        $checkoutSelectionRules = $this->configService->get('AcrisStoreLocatorCS.config.checkoutSelectionRules', $salesChannelId);
        $addActiveStores = true;


        if (!empty($checkoutSelectionRules)) {
            foreach ($checkoutSelectionRules as $rule) {
                if (!in_array($rule, $event->getSalesChannelContext()->getRuleIds())) {
                    $addActiveStores = false;
                } else {
                    $addActiveStores = true;
                    break;
                }
            }
        }

        if ($addActiveStores) {
            /** @var StoreLocatorCollection $activeStores */
            $activeStores = $this->storeLocatorService->getActiveStores($event->getSalesChannelContext()->getContext(), $salesChannelId, $event->getSalesChannelContext()->getRuleIds());
            if ($activeStores->count() === 0) {
                return;
            }
            $event->getPage()->addExtension(self::STORE_LOCATOR_STORES_EXTENSION, $activeStores);
            $event->getPage()->addExtension(self::STORE_LOCATOR_CONFIG_EXTENSION, new StoreLocatorConfigStruct(
                $checkoutSelection,
                $this->configService->get('AcrisStoreLocatorCS.config.checkoutSelectionRequired', $salesChannelId)
            ));
        }
    }

    public function onCheckoutOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        if (!empty($event->getContext()) && $event->getContext()->hasExtension('AcrisStoreLocatorSelection')) {
            $storeId = $event->getContext()->getExtension('AcrisStoreLocatorSelection')->get('value');
            if (empty($storeId) && $storeId === self::DEFAULT_VALUE_NO_SELECT) return;

            $order = $event->getOrder();
            if (empty($order) || empty($order->getDeliveries())) return;

            /** @var  $stores StoreLocatorCollection */
            $stores = $this->storeLocatorService->getActiveStores($event->getContext());
            if ($stores->count() > 0 && $stores->has($storeId)) {
                $store = $stores->get($storeId);
                $insert = [];
                foreach ($order->getDeliveries() as $delivery) {
                    $id = Uuid::randomHex();
                    $data = [
                        'id' => $id,
                        'storeId' => $storeId,
                        'orderDeliveryId' => $delivery->getId(),
                        'countryId' => $store->getCountryId(),
                        'name' => $store->getTranslation('name'),
                        'department' => $store->getTranslation('department'),
                        'city' => $store->getCity(),
                        'zipcode' => $store->getZipcode(),
                        'street' => $store->getStreet(),
                        'phone' => $store->getTranslation('phone'),
                        'email' => $store->getTranslation('email'),
                        'url' => $store->getTranslation('url'),
                        'opening_hours' => $store->getTranslation('opening_hours'),
                        'longitude' => $store->getLongitude(),
                        'latitude' => $store->getLatitude()
                    ];
                    $insert[] = $data;
                }
                if (!empty($insert)) {
                    $this->storeLocatorService->insertData($insert, $event->getContext());
                    foreach ($order->getDeliveries() as $delivery) {
                        $deliveryStore = $this->storeLocatorService->getDeliveryStore($delivery->getId(), $storeId, $event->getContext());
                        if (!empty($deliveryStore)) {
                            if ($delivery->hasExtension('acrisOrderDeliveryStore') && empty($delivery->getExtension('acrisOrderDeliveryStore'))) {
                                $delivery->removeExtension('acrisOrderDeliveryStore');
                                $delivery->addExtension('acrisOrderDeliveryStore', $deliveryStore);
                            } else {
                                $delivery->addExtension('acrisOrderDeliveryStore', $deliveryStore);
                            }
                        }
                    }
                }
            }
        }
    }

    public function onAccountOverviewPageLoaded(AccountOverviewPageLoadedEvent $event): void
    {
        $stores = $this->storeLocatorService->getActiveStores($event->getSalesChannelContext()->getContext());

        if ($stores->count() === 0) return;

        $event->getPage()->addExtension(self::STORE_LOCATOR_STORES_EXTENSION, $stores);

        $customer = $event->getSalesChannelContext()->getCustomer();
        if (empty($customer) || empty($customer->getCustomFields()) || !is_array($customer->getCustomFields()) || !array_key_exists(StoreLocatorService::STORE_LOCATOR_ASSIGNED_STORE, $customer->getCustomFields()) || empty($customer->getCustomFields()[StoreLocatorService::STORE_LOCATOR_ASSIGNED_STORE])) return;

        $store = $stores->get($customer->getCustomFields()[StoreLocatorService::STORE_LOCATOR_ASSIGNED_STORE]);
        if (empty($store)) return;

        $this->storeLocatorService->setDefaultCmsPageForStore($store, $event->getSalesChannelContext());

        $customer->addExtension(StoreLocatorService::STORE_LOCATOR_ASSIGNED_STORE_EXTENSION, $store);
    }

    public function onAccountLoginPageLoaded(AccountLoginPageLoadedEvent $event): void
    {
        $stores = $this->storeLocatorService->getActiveStores($event->getSalesChannelContext()->getContext());

        if ($stores->count() === 0) return;

        $event->getPage()->addExtension(self::STORE_LOCATOR_STORES_EXTENSION, $stores);
    }

    public function onRegisterCustomerEvent(DataMappingEvent $event): void
    {
        $output = $event->getOutput();
        $input = $event->getInput();

        if (!$input->get(StoreLocatorController::STORE_LOCATOR_STORE_SELECTION_ACCOUNT_KEY)) {
            return;
        }

        $storeId = $input->get(StoreLocatorController::STORE_LOCATOR_STORE_SELECTION_ACCOUNT_KEY);

        if ($storeId === StoreLocatorService::SELECT_OPTION_NO_SELECT) {
            return;
        }

        $input->set('customFields', [
            'acris_store_locator_assigned_store' => $storeId
        ]);

        $customFields = !empty($output) && array_key_exists('customFields', $output) && !empty($output['customFields']) ? $output['customFields'] : [];

        $customFields['acris_store_locator_assigned_store'] = $storeId;
        $output['customFields'] = $customFields;

        $event->setOutput($output);
    }
}
