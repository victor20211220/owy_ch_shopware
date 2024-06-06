<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT32887;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class InvalidSQLInputException extends ShopwareHttpException
{
    public function getErrorCode(): string
    {
        return 'SWAG_SECURITY_INVALID_INPUT';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
