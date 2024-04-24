<?php declare(strict_types=1);

namespace Acris\StoreLocator\Components;

use Acris\StoreLocator\AcrisStoreLocatorCS as AcrisStoreLocator;
use Acris\StoreLocator\Core\Content\Cms\SalesChannel\Struct\StoreOriginalData;
use Acris\StoreLocator\Core\Content\StoreLocator\Exception\PageForStoreNotFoundException;
use Acris\StoreLocator\Core\Content\StoreLocator\Exception\StorePageNotActiveException;
use Acris\StoreLocator\Core\Content\StoreLocator\Exception\StoreWithIdNotFoundException;
use Acris\StoreLocator\Core\Framework\DataAbstractionLayer\Exception\StoreGroupNotFoundException;
use Acris\StoreLocator\Custom\OrderDeliveryStoreEntity;
use Acris\StoreLocator\Custom\StoreGroupCollection;
use Acris\StoreLocator\Custom\StoreGroupEntity;
use Acris\StoreLocator\Custom\StoreLocatorCollection;
use Acris\StoreLocator\Custom\StoreLocatorDefinition;
use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

class StoreLocatorService
{
    public const STORE_LOCATOR_ASSIGNED_STORE = 'acris_store_locator_assigned_store';
    public const STORE_LOCATOR_ASSIGNED_STORE_EXTENSION = 'acrisStoreLocatorAssignedStore';
    public const SELECT_OPTION_NO_SELECT = 'noSelect';

    public function __construct(
        private readonly StoreLocatorGateway $storeLocatorGateway,
        private readonly SalesChannelCmsPageLoaderInterface $cmsPageLoader,
        private readonly StoreLocatorDefinition $storeDefinition,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $cmsPageRepository,
        private readonly EntityRepository $customerRepository
    )
    {
    }

    public function getActiveStores(Context $context, ?string $salesChannelId = null, ?array $ruleIds = null): EntityCollection
    {
        return $this->storeLocatorGateway->loadActiveStores($context, $salesChannelId, $ruleIds);
    }

    public function insertData(array $data, Context $context): void
    {
        $this->storeLocatorGateway->insertOrderStoreDelivery($data, $context);
    }

    public function getDeliveryStore(string $deliveryId, string $storeId, Context $context): ?OrderDeliveryStoreEntity
    {
        return $this->storeLocatorGateway->loadDeliveryStore($deliveryId, $storeId, $context);
    }

    public function loadStoreGroups(SalesChannelContext $salesChannelContext): StoreGroupCollection
    {
        return $this->storeLocatorGateway->getStoreGroups($salesChannelContext);
    }

    public function getStoreGroupWithId(string $groupId, SalesChannelContext $salesChannelContext): StoreGroupEntity
    {
        $storeGroup = $this->storeLocatorGateway->getStoreGroups($salesChannelContext)->get($groupId);
        if (empty($storeGroup)) throw new StoreGroupNotFoundException("Store group with id {$groupId} was not found!");
        return $storeGroup;
    }

    public function loadStoresForCurrentGroup(string $groupId, SalesChannelContext $salesChannelContext): StoreLocatorCollection
    {
        $stores = $this->storeLocatorGateway->getStoresForGroup($groupId, $salesChannelContext);
        $this->assignOriginalStoreData($stores, $salesChannelContext);
        $this->encryptStoreData($stores, $salesChannelContext);
        $this->setDefaultCmsPage($stores, $salesChannelContext);

        return $stores;
    }

    public function checkStoresHasCover(StoreLocatorCollection $stores): bool
    {
        if ($stores->count() === 0) return false;

        $hasCover = false;
        foreach ($stores->getElements() as $store) {
            if (!empty($store->getCoverId()) && !empty($store->getCover())) {
                $hasCover = true;
            }
        }

        return $hasCover;
    }

    public function loadStoreGroupById(string $groupId, SalesChannelContext $salesChannelContext): ?StoreGroupEntity
    {
        return $this->storeLocatorGateway->getStoreGroupById($groupId, $salesChannelContext);
    }

    public function getStoreById(string $storeId, SalesChannelContext $salesChannelContext, Request $request): ?StoreLocatorEntity
    {
        $store = $this->storeLocatorGateway->loadStoreById($storeId, $salesChannelContext);
        $this->storeLocatorGateway->assignOriginalStoreData($store, $salesChannelContext);
        $this->storeLocatorGateway->encryptStoreData($store, $salesChannelContext);
        if (empty($store)) throw new StoreWithIdNotFoundException($storeId);
        $this->setDefaultCmsPageForStore($store, $salesChannelContext);
        if (empty($store->getCmsPageId())) throw new PageForStoreNotFoundException($storeId);

        if (!empty($store) && $store->isActive() !== true) {
            throw new StorePageNotActiveException($storeId);
        }

        $resolverContext = new EntityResolverContext($salesChannelContext, $request, $this->storeDefinition, $store);
        $store->setCmsPage($this->cmsPageLoader->load($request, new Criteria([$store->getCmsPageId()]), $salesChannelContext, $store->getTranslation('slotConfig'), $resolverContext)->first());

        return $store;
    }

    public function assignStoreToCustomer(string $storeId, SalesChannelContext $salesChannelContext): void
    {
        $customer = $salesChannelContext->getCustomer();

        if (empty($customer)) return;

        $changeExist = false;
        $customFields = !empty($customer->getCustomFields()) && is_array($customer->getCustomFields()) ? $customer->getCustomFields() : [];

        if ($storeId === self::SELECT_OPTION_NO_SELECT) {
            if (array_key_exists(self::STORE_LOCATOR_ASSIGNED_STORE, $customFields)) {
                unset($customFields[self::STORE_LOCATOR_ASSIGNED_STORE]);
                $changeExist = true;
            }
        } else {
            $customFields[self::STORE_LOCATOR_ASSIGNED_STORE] = $storeId;
            $changeExist = true;
        }

        if ($changeExist !== true) return;

        $this->customerRepository->update([
            [
                'id' => $customer->getId(),
                'customFields' => $customFields
            ]
        ], $salesChannelContext->getContext());
    }

    public function setDefaultCmsPageForStore(StoreLocatorEntity $store, SalesChannelContext $salesChannelContext): void
    {
        if (empty($store->getCmsPageId())) {
            $cmsPageName = AcrisStoreLocator::DEFAULT_STORE_LAYOUT_CMS_PAGE_NAME;
            $existingSearchResult = $this->cmsPageRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $cmsPageName))->addFilter(new EqualsFilter('locked', true)), $salesChannelContext->getContext());
            if($existingSearchResult->getTotal() > 0 && $existingSearchResult->firstId()) {
                $store->setCmsPageId($existingSearchResult->firstId());
            }
        }
    }

    private function encryptStoreData(StoreLocatorCollection $stores, SalesChannelContext $salesChannelContext): void
    {
        if ($this->systemConfigService->get('AcrisStoreLocatorCS.config.encryptMail', $salesChannelContext->getSalesChannel()->getId())) {
            if ($stores->count() > 0 && $stores->first()) {
                foreach ($stores as $key => $store) {
                    if (!empty($store->getTranslation('email'))) {
                        $store->addTranslated('email', $this->strRot16($store->getTranslation('email')));
                    }
                }
            }
        }
    }

    private function assignOriginalStoreData(StoreLocatorCollection $stores, SalesChannelContext $salesChannelContext): void
    {
        $showEmail = $this->systemConfigService->get('AcrisStoreLocatorCS.config.showEmail', $salesChannelContext->getSalesChannel()->getId());

        if (!empty($showEmail) && ($showEmail === 'iconAndEmail' || $showEmail === 'onlyEmail')) {
            if ($stores->count() > 0 && $stores->first()) {
                foreach ($stores as $store) {
                    if (!empty($store->getTranslation('email'))) {
                        $store->addExtension('originalData', new StoreOriginalData($store->getTranslation('email')));
                    }
                }
            }
        }
    }

    private function strRot16($string): string
    {
        $value = '';
        for ($i = 0, $j = strlen($string); $i < $j; $i++)
        {
            // Get the ASCII character for the current character
            $char = ord($string[$i]);

            // If that character is in the range A-Z or a-z, add 13 to its ASCII value
            if( ($char >= 65  && $char <= 90) || ($char >= 97 && $char <= 122))
            {
                $char += 16;

                // If we should have wrapped around the alphabet, subtract 26
                if( $char > 122 || ( $char > 90 && ord( $string[$i]) <= 90))
                {
                    $char -= 26;
                }
            }
            $value .= chr($char);
        }

        return $value;
    }

    private function setDefaultCmsPage(StoreLocatorCollection $stores, SalesChannelContext $salesChannelContext): void
    {
        if ($stores->count() > 0 && $stores->first()) {
            foreach ($stores as $key => $store) {
                if (empty($store->getCmsPageId())) {
                    $cmsPageName = AcrisStoreLocator::DEFAULT_STORE_LAYOUT_CMS_PAGE_NAME;
                    $existingSearchResult = $this->cmsPageRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $cmsPageName))->addFilter(new EqualsFilter('locked', true)), $salesChannelContext->getContext());
                    if($existingSearchResult->getTotal() > 0 && $existingSearchResult->firstId()) {
                        $store->setCmsPageId($existingSearchResult->firstId());
                    }
                }
            }
        }
    }
}
