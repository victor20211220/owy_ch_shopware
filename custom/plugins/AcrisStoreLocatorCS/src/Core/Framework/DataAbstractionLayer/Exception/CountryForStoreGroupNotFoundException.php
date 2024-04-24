<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Framework\DataAbstractionLayer\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CountryForStoreGroupNotFoundException extends ShopwareHttpException
{
    public function __construct(string $id)
    {
        parent::__construct(
            'Country with entered data "{{ countryId }}" was not found.',
            ['countryId' => $id]
        );
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__COUNTRY_FOR_STORE_GROUP_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
