<?php declare(strict_types=1);

namespace Ott\SelectlineImport\ImportTypeProcessor;

use Ott\Base\Import\CollectionService;
use Ott\SelectlineImport\Dbal\Entity\ImportPictureMessageEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class PictureImportProcessor extends ImportProcessor
{
    public function import(ImportPictureMessageEntity $message): void
    {
        $data = $message->getWorkload();
        $productId = $message->getProductId();
        $product = new ProductEntity();
        $product->setId($productId);

        $mediaIds = [];
        foreach ($data['pictures'] as $picture) {
            $mediaId = $this->getMediaId($picture);
            if (null !== $mediaId) {
                $mediaIds[] = $mediaId;
            }
        }

        if (!empty($mediaIds)) {
            $productMediaEntities = [];
            foreach ($mediaIds as $mediaId) {
                $productMediaEntity = new ProductMediaEntity();
                $productMediaEntity->setMediaId($mediaId);
                $productMediaEntity->setProduct($product);
                $productMediaEntities[] = $productMediaEntity;
            }
            $mediaCollection = CollectionService::buildCollection(
                $productMediaEntities,
                ProductMediaCollection::class
            );
            $product->setMedia($mediaCollection);
        } else {
            $coverMediaId = $this->importExtensionGateway->getCoverMediaId($productId);

            if (null !== $coverMediaId) {
                $product->setCoverId($coverMediaId);
            }
        }

        if (null !== $product->getMedia()) {
            foreach ($product->getMedia() as $medium) {
                $medium = $this->importService->importProductMedia($medium);
                $this->productModule->storeProductMedia(
                    $medium->getId(),
                    $product->getId(),
                    $medium->getMediaId(),
                    $this->hasValue($medium, 'position') ? $medium->getPosition() : 1,
                    $medium->getCustomFields()
                );

                if (null === $product->getCoverId()) {
                    $product->setCoverId($medium->getId());
                }
            }
        }

        if (null !== $product->getCoverId()) {
            $this->importExtensionGateway->updateProductCover($product->getId(), $product->getCoverId());
        }
    }

    private function getMediaId(string $imageUrl): ?string
    {
        $fileParts = explode('/', $imageUrl);
        $fileName = str_replace('.jpg', '', array_pop($fileParts));
        $mediaId = $this->importService->getMediaId($fileName, 'jpg');

        if (null === $mediaId) {
            try {
                $imageContent = file_get_contents($imageUrl);
                if ($imageContent) {
                    $mediaId = $this->mediaService->saveMediaFromBlob($imageContent, 'jpg', $fileName, 'product');
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    '%s[%s]: %s',
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                ));
            }
        }

        return $mediaId;
    }

    private function hasValue(Entity $entity, string $valueName = 'id'): bool
    {
        $entityArray = $entity->jsonSerialize();

        return isset($entityArray[$valueName]) && null !== $entityArray[$valueName];
    }
}
