<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Framework\DataAbstractionLayer\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class StoreGroupNotFoundException extends ShopwareHttpException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__DEFAULT_STORE_GROUP_NOT_FOUND';
    }
}
