<?php declare(strict_types=1);

namespace Ott\Base\Service;

use GuzzleHttp\Psr7\MimeType;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Ott\Base\Import\Module\MediaModule;
use Shopware\Core\Content\Media\File\FileFetcher;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Media\Thumbnail\ThumbnailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

class MediaHelper
{
    private MediaService $mediaService;
    private FileFetcher $fileFetcher;
    private FileSaver $fileSaver;
    private EntityRepository $mediaRepository;
    private Filesystem $filesystemPublic;
    private Filesystem $filesystemPrivate;
    private UrlGeneratorInterface $urlGenerator;
    private ThumbnailService $thumbnailService;
    private MediaModule $mediaModule;

    public function __construct(
        MediaService $mediaService,
        FileFetcher $fileFetcher,
        FileSaver $fileSaver,
        EntityRepository $entityRepository,
        Filesystem $filesystemPublic,
        Filesystem $filesystemPrivate,
        UrlGeneratorInterface $urlGenerator,
        ThumbnailService $thumbnailService,
        MediaModule $mediaModule
    )
    {
        $this->mediaService = $mediaService;
        $this->fileFetcher = $fileFetcher;
        $this->fileSaver = $fileSaver;
        $this->mediaRepository = $entityRepository;
        $this->filesystemPublic = $filesystemPublic;
        $this->filesystemPrivate = $filesystemPrivate;
        $this->urlGenerator = $urlGenerator;
        $this->thumbnailService = $thumbnailService;
        $this->mediaModule = $mediaModule;
    }

    public function saveMediaFromFile(string $file, ?string $filename = null, string $folder = '', bool $private = false): string
    {
        $fileInfo = $this->getFileInfo($file);
        $mediaFile = new MediaFile($file, $fileInfo['mimeType'], $fileInfo['extension'], $fileInfo['fileSize']);

        return $this->saveMedia($mediaFile, $filename ?? $fileInfo['filename'], $folder, $private);
    }

    public function saveMediaFromBlob(string $blob, string $extension, ?string $filename = null, ?string $folder = '', ?bool $private = false): string
    {
        $mediaFile = $this->fileFetcher->fetchBlob($blob, $extension, MimeType::fromExtension($extension));

        return $this->saveMedia($mediaFile, $filename, $folder, $private);
    }

    public function getMedia(string $file): ?MediaEntity
    {
        $fileInfo = $this->getFileInfo($file);
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [
                new EqualsFilter('fileName', $fileInfo['filename']),
                new EqualsFilter('fileExtension', $fileInfo['extension']),
            ]
        ));

        /** @var MediaCollection $mediaCollection */
        $mediaCollection = $this->mediaRepository->search($criteria, Context::createDefaultContext())->getEntities();

        if (null === $mediaCollection) {
            return null;
        }

        return $mediaCollection->first();
    }

    public function getMediaById(string $id): ?MediaEntity
    {
        /** @var MediaCollection $mediaCollection */
        $mediaCollection = $this->mediaRepository->search(
            new Criteria([$id]),
            Context::createDefaultContext()
        )->getEntities();

        if (null === $mediaCollection) {
            return null;
        }

        return $mediaCollection->first();
    }

    public function deleteMedia(MediaEntity $mediaEntity, bool $isIdOnly = false): void
    {
        if ($isIdOnly) {
            $mediaEntity = $this->getMediaById($mediaEntity->getId());
            if (null === $mediaEntity) {
                return;
            }
        }

        if (!$mediaEntity->hasFile()) {
            return;
        }

        $oldMediaFilePath = $this->urlGenerator->getRelativeMediaUrl($mediaEntity);
        try {
            $this->getFileSystem($mediaEntity)->delete($oldMediaFilePath);
        } catch (FileNotFoundException $fileNotFoundException) {
            //nth
        }
        $this->thumbnailService->deleteThumbnails($mediaEntity, Context::createDefaultContext());
        $this->mediaRepository->delete([['id' => $mediaEntity->getId()]], Context::createDefaultContext());
    }

    public function getMediaPathById(string $id): ?string
    {
        $media = $this->mediaModule->selectMediaById($id);
        $hashParts = str_split(
            substr(
                md5(strtotime($media['uploaded_at']) . '/' . $media['file_name']),
                0,
                6
            ),
            2
        );

        foreach ($hashParts as $key => $value) {
            $hashParts[$key] = str_replace('ad', 'g0', $value);
        }

        return sprintf(
            '/media/%s/%s/%s.%s',
            implode('/', $hashParts),
            strtotime($media['uploaded_at']),
            $media['file_name'],
            $media['file_extension']
        );
    }

    private function getFileSystem(MediaEntity $mediaEntity): Filesystem
    {
        if ($mediaEntity->isPrivate()) {
            return $this->filesystemPrivate;
        }

        return $this->filesystemPublic;
    }

    private function saveMedia(MediaFile $mediaFile, string $filename, string $folder, bool $private): string
    {
        $context = Context::createDefaultContext();
        $mediaId = $this->mediaService->createMediaInFolder($folder, $context, $private);
        $this->fileSaver->persistFileToMedia($mediaFile, $filename, $mediaId, $context);

        return $mediaId;
    }

    /**
     * @return array{extension: string, filename: string, mimeType: string|true, fileSize: int|true}
     */
    public function getFileInfo(string $file): array
    {
        $fileArray = explode('/', $file);
        $fileName = array_pop($fileArray);
        $fileNameArray = explode('.', $fileName);
        $extension = array_pop($fileNameArray);
        $fileName = implode('.', $fileNameArray);

        return [
            'extension' => $extension,
            'filename'  => $fileName,
            'mimeType'  => mime_content_type($file),
            'fileSize'  => filesize($file),
        ];
    }
}
