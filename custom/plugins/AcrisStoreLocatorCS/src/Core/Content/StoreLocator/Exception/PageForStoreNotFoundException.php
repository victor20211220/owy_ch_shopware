<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\StoreLocator\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class PageForStoreNotFoundException extends ShopwareHttpException
{
    public function __construct(string $storeId)
    {
        parent::__construct(
            'Page for store with id "{{ storeId }}" was not found.',
            ['storeId' => $storeId]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'STORE_CONTENT__CMS_PAGE_NOT_FOUND';
    }
}
