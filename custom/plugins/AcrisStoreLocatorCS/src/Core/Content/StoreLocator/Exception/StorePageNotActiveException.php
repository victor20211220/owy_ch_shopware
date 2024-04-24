<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\StoreLocator\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class StorePageNotActiveException extends ShopwareHttpException
{
    public function __construct(string $storeId)
    {
        parent::__construct(
            'Store page with id "{{ storeId }}" is not active.',
            ['storeId' => $storeId]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'STORE_CONTENT__STORE_PAGE_NOT_ACTIVE';
    }
}
