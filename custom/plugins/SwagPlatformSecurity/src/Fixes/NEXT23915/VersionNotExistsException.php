<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23915;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class VersionNotExistsException extends ShopwareHttpException
{
    public function __construct(string $versionId)
    {
        parent::__construct(
            'Version {{ versionId }} does not exist. Version was probably deleted or already merged.',
            ['versionId' => $versionId]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__VERSION_NOT_EXISTS';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
