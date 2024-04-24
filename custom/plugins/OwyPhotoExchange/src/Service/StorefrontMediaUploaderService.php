<?php declare(strict_types=1);

namespace OwyPhotoExchange\Service;

use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\IllegalFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\Exception\UploadException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Storefront\Framework\Media\Exception\FileTypeNotAllowedException;
use Shopware\Storefront\Framework\Media\StorefrontMediaValidatorRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StorefrontMediaUploaderService
{
    /**
     * @var FileSaver
     */
    private FileSaver $fileSaver;

    /**
     * @var MediaService
     */
    private MediaService $mediaService;

    /**
     * @var StorefrontMediaValidatorRegistry
     */
    private StorefrontMediaValidatorRegistry $validator;

    public function __construct(MediaService $mediaService, FileSaver $fileSaver, StorefrontMediaValidatorRegistry $validator)
    {
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->validator = $validator;
    }

    /**
     * @throws FileTypeNotAllowedException
     * @throws IllegalFileNameException
     * @throws UploadException
     * @throws DuplicatedMediaFileNameException
     * @throws EmptyMediaFilenameException
     */
    public function upload(UploadedFile $file, string $folder, string $type, Context $context): string
    {
        $this->checkValidFile($file);

        $this->validator->validate($file, $type);
        $fileNameExplode = $file->getClientOriginalName();
        $fileName = explode(".",$fileNameExplode);

        $mediaFile = new MediaFile($file->getPathname(), $file->getMimeType(), $file->getClientOriginalExtension(), $file->getSize());

        $mediaId = $this->mediaService->createMediaInFolder('owy-photo-exchange', $context, false);

        try {
            $this->fileSaver->persistFileToMedia(
                $mediaFile,
                pathinfo($fileName[0] . '-' .date('Y-m-d His') , PATHINFO_FILENAME),
                $mediaId,
                $context
            );
        } catch (MediaNotFoundException $e) {
            throw new UploadException($e->getMessage());
        }

        return $mediaId;
    }

    private function checkValidFile(UploadedFile $file): void
    {
        if (!$file->isValid()) { // here was a `!` missing
            throw new UploadException($file->getErrorMessage());
        }

        if (preg_match('/.+\.ph(p([3457s]|-s)?|t|tml)/', $file->getFilename())) {
            throw new IllegalFileNameException($file->getFilename(), 'contains PHP related file extension');
        }
    }
}