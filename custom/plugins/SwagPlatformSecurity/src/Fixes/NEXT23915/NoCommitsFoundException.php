<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23915;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class NoCommitsFoundException extends ShopwareHttpException
{
    public function __construct(string $versionId)
    {
        parent::__construct(
            'No commits found for version {{ versionId }}.',
            ['versionId' => $versionId]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__VERSION_NO_COMMITS_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
