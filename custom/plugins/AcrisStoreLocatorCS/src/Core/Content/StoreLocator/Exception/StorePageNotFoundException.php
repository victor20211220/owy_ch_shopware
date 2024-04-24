<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\StoreLocator\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class StorePageNotFoundException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct(
            'Page was not found.'
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'STORE_CONTENT__PAGE_NOT_FOUND';
    }
}
