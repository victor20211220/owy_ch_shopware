<?php declare(strict_types=1);

namespace Acris\StoreLocator\Components;

use Acris\StoreLocator\Core\Content\Cms\SalesChannel\Struct\StoreOriginalData;
use Acris\StoreLocator\Custom\OrderDeliveryStoreEntity;
use Acris\StoreLocator\Custom\StoreGroupCollection;
use Acris\StoreLocator\Custom\StoreGroupEntity;
use Acris\StoreLocator\Custom\StoreLocatorCollection;
use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class StoreLocatorGateway
{
    public function __construct(
        private readonly EntityRepository $storeLocatorRepository,
        private readonly EntityRepository $storeOrderDeliveryRepository,
        private readonly EntityRepository $storeGroupRepository,
        private readonly EntityRepository $mediaRepository,
        private readonly SystemConfigService $systemConfigService)
    {
    }

    public function loadActiveStores(Context $context, ?string $salesChannelId = null, ?array $ruleIds = null): EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('country')
            ->addAssociation('state')
            ->addFilter(new EqualsFilter('active', true));

        if (!empty($salesChannelId)) {
            $criteria->addFilter(
                new OrFilter([
                    new EqualsFilter('salesChannels.id', null),
                    new EqualsFilter('salesChannels.id', $salesChannelId)
                ]));
        }

        if (!empty($ruleIds)) {
            $criteria->addFilter(new OrFilter([
                new EqualsFilter('rules.id', null),
                new EqualsAnyFilter('rules.id', $ruleIds)]));
        }


        return $this->storeLocatorRepository->search($criteria, $context)->getEntities();
    }

    public function insertOrderStoreDelivery(array $data, Context $context): void
    {
        $this->storeOrderDeliveryRepository->upsert($data, $context);
    }

    public function loadDeliveryStore(string $deliveryId, string $storeId, Context $context): ?OrderDeliveryStoreEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('storeId', $storeId),
            new EqualsFilter('orderDeliveryId', $deliveryId)
        ]));
        $criteria->addAssociation('country')->addAssociation('state');

        return $this->storeOrderDeliveryRepository->search($criteria, $context)->first();
    }

    public function getStoreGroups(SalesChannelContext $salesChannelContext): StoreGroupCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('media');
        $criteria->addAssociation('icon');
        $criteria->addAssociation('acrisStores');
        $criteria->addSorting(new FieldSorting('priority', FieldSorting::DESCENDING, true))->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('active', true),
                ]
            )
        );
        $criteria->getAssociation('acrisStores')
            ->addFilter(
                new OrFilter([
                    new EqualsFilter('salesChannels.id', null),
                    new EqualsFilter('salesChannels.id', $salesChannelContext->getSalesChannelId())
                ])
            );

        /** @var StoreGroupCollection $groups */
        $groups = $this->storeGroupRepository->search($criteria, $salesChannelContext->getContext())->getEntities();
        /** @var StoreGroupEntity $group */
        foreach ($groups->getElements() as $group) {
            if ($group->getAcrisStores()->count() <= 0) {
                $groups->remove($group->getId());
            }
        }

        return $groups;
    }

    public function getStoresForGroup(string $groupId, SalesChannelContext $salesChannelContext): StoreLocatorCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('country');
        $criteria->addAssociation('state');
        $criteria->addAssociation('cover.media');
        $criteria->addAssociation('media');
        $criteria->addSorting(new FieldSorting('priority', FieldSorting::DESCENDING, true));
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('active', true),
                    new EqualsFilter('storeGroupId', $groupId)
                ]
            )
        );
        $criteria->addFilter(
            new OrFilter([
                new EqualsFilter('salesChannels.id', null),
                new EqualsFilter('salesChannels.id', $salesChannelContext->getSalesChannelId())
            ])
        );
        return $this->storeLocatorRepository->search($criteria, $salesChannelContext->getContext())->getEntities();
    }

    public function getMediaForDefaultGroup(SalesChannelContext $salesChannelContext): MediaEntity
    {
        return $this->mediaRepository->search((new Criteria())->addFilter(new EqualsFilter('fileName', 'plugin'))->addFilter(new EqualsFilter('fileExtension', 'png')), $salesChannelContext->getContext())->first();
    }

    public function loadStoreById(string $storeId, SalesChannelContext $salesChannelContext): ?StoreLocatorEntity
    {
        return $this->storeLocatorRepository->search($this->getCriteria($storeId, $salesChannelContext), $salesChannelContext->getContext())->first();
    }

    public function loadStoreByIdForElement(string $storeId, SalesChannelContext $salesChannelContext): ?StoreLocatorEntity
    {
        $store = $this->storeLocatorRepository->search($this->getCriteria($storeId, $salesChannelContext), $salesChannelContext->getContext())->first();
        $this->assignOriginalStoreData($store, $salesChannelContext);
        $this->encryptStoreData($store, $salesChannelContext);

        return $store;
    }

    public function encryptStoreData(StoreLocatorEntity $store, SalesChannelContext $salesChannelContext): void
    {
        if ($this->systemConfigService->get('AcrisStoreLocatorCS.config.encryptMail', $salesChannelContext->getSalesChannel()->getId())) {
            {
                if (!empty($store->getTranslation('email'))) {
                    $store->addTranslated('email', $this->strRot16($store->getTranslation('email')));
                }
            }
        }
    }

    public function assignOriginalStoreData(StoreLocatorEntity $store, SalesChannelContext $salesChannelContext): void
    {
        $showEmail = $this->systemConfigService->get('AcrisStoreLocatorCS.config.showEmail', $salesChannelContext->getSalesChannel()->getId());

        if (!empty($showEmail) && ($showEmail === 'iconAndEmail' || $showEmail === 'onlyEmail')) {
            if (!empty($store->getTranslation('email'))) {
                $store->addExtension('originalData', new StoreOriginalData($store->getTranslation('email')));
            }
        }
    }

    private function getCriteria(string $storeId, SalesChannelContext $salesChannelContext): Criteria
    {
        $criteria = new Criteria([$storeId]);
        $criteria->addAssociation('country')
            ->addAssociation('state')
            ->addAssociation('cmsPage')
            ->addAssociation('storeGroup')
            ->addAssociation('cover.media')
            ->addAssociation('media')
            ->addAssociation('acrisOrderDeliveryStore')
            ->addFilter(
                new OrFilter([
                    new EqualsFilter('salesChannels.id', null),
                    new EqualsFilter('salesChannels.id', $salesChannelContext->getSalesChannelId())
                ])
            );

        return $criteria;
    }

    private function strRot16($string): string
    {
        $value = '';
        for ($i = 0, $j = strlen($string); $i < $j; $i++) {
            // Get the ASCII character for the current character
            $char = ord($string[$i]);

            // If that character is in the range A-Z or a-z, add 13 to its ASCII value
            if (($char >= 65 && $char <= 90) || ($char >= 97 && $char <= 122)) {
                $char += 16;

                // If we should have wrapped around the alphabet, subtract 26
                if ($char > 122 || ($char > 90 && ord($string[$i]) <= 90)) {
                    $char -= 26;
                }
            }
            $value .= chr($char);
        }

        return $value;
    }

    public function getStoreGroupById(string $id, SalesChannelContext $salesChannelContext): ?StoreGroupEntity
    {
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('media');
        $criteria->addAssociation('icon');
        $criteria->addAssociation('acrisStores');
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('active', true),
                ]
            )
        );
        $criteria->getAssociation('acrisStores')
            ->addFilter(
                new OrFilter([
                    new EqualsFilter('salesChannels.id', null),
                    new EqualsFilter('salesChannels.id', $salesChannelContext->getSalesChannelId())
                ])
            );

        $group = $this->storeGroupRepository->search($criteria, $salesChannelContext->getContext())->first();

        if (empty($group) || $group->getAcrisStores()->count() === 0) return null;

        return $group;
    }
}
