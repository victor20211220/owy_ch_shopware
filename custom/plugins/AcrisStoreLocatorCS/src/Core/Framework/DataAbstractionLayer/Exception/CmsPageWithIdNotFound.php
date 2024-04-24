<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Framework\DataAbstractionLayer\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CmsPageWithIdNotFound extends ShopwareHttpException
{
    public function __construct(string $cmsPageId)
    {
        parent::__construct(
            'Cms page with id "{{ cmsPageId }}" was not found.',
            ['cmsPageId' => $cmsPageId]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__DEFAULT_CMS_PAGE_WITH_ID_NOT_FOUND';
    }
}
