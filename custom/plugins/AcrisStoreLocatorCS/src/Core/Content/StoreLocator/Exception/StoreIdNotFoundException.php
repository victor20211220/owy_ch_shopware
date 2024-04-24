<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\StoreLocator\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class StoreIdNotFoundException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct(
            'Store id is empty.'
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'STORE_CONTENT__STORE_EMPTY_ID';
    }
}
