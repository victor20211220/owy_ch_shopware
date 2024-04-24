<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\Cms\DataResolver\Element;

use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Acris\StoreLocator\Custom\StoreMediaCollection;
use Acris\StoreLocator\Custom\StoreMediaEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ImageSliderItemStruct;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ImageSliderStruct;
use Shopware\Core\Content\Media\Cms\Type\ImageSliderTypeDataResolver;

// this is image-slider resolver extension
// IMPORTANT: image-gallery element extends this resolver and changes type to 'image-gallery' (Shopware standard)
// we override image-gallery resolver to extend this resolver
class ImageGallerySliderResolver extends ImageSliderTypeDataResolver implements CmsElementResolverInterface
{
    private CmsElementResolverInterface $parent;

    public function __construct(
        CmsElementResolverInterface  $parent
    )
    {
        $this->parent = $parent;
    }

    public function getType(): string
    {
        return $this->parent->getType();
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
       return $this->parent->collect($slot, $resolverContext);
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();

        $sliderItemsConfig = $config->get('sliderItems');

        if ($sliderItemsConfig === null) {
            return;
        }

        if ($sliderItemsConfig->isMapped() && $resolverContext instanceof EntityResolverContext && $sliderItemsConfig->getStringValue() === 'acris_store_locator.media') {
            $imageSlider = new ImageSliderStruct();
            $slot->setData($imageSlider);

            $navigation = $config->get('navigation');
            if ($navigation !== null && $navigation->isStatic()) {
                $imageSlider->setNavigation($navigation->getArrayValue());
            }

            if ($sliderItemsConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
                $sliderItems = $this->resolveEntityValue($resolverContext->getEntity(), $sliderItemsConfig->getStringValue());

                if ($sliderItems === null || \count($sliderItems) < 1) {
                    return;
                }

                // for store_locator mapping
                if ($sliderItemsConfig->getStringValue() === 'acris_store_locator.media') {
                    $this->sortStoreMediaItemsByPosition($sliderItems);
                    /** @var StoreLocatorEntity $storeLocatorEntity */
                    $storeLocatorEntity = $resolverContext->getEntity();

                    if ($storeLocatorEntity->getCoverId()) {
                        /** @var StoreMediaCollection $sliderItems */
                        $sliderItems = new StoreMediaCollection(array_merge(
                            [$storeLocatorEntity->getCoverId() => $storeLocatorEntity->getCover()],
                            $sliderItems->getElements()
                        ));
                    }
                }

                foreach ($sliderItems->getMedia() as $media) {
                    $imageSliderItem = new ImageSliderItemStruct();
                    $imageSliderItem->setMedia($media);
                    $imageSlider->addSliderItem($imageSliderItem);
                }
            }
        } else {
            $this->parent->enrich($slot, $resolverContext, $result);
        }
    }

    protected function sortStoreMediaItemsByPosition(StoreMediaCollection $sliderItems): void
    {
        if (!$sliderItems->first() || !$sliderItems->first()->has('position')) {
            return;
        }

        $sliderItems->sort(static function (StoreMediaEntity $a, StoreMediaEntity $b) {
            return $a->get('position') - $b->get('position');
        });
    }
}
