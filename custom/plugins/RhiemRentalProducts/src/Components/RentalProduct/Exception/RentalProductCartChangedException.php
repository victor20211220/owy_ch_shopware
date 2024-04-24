<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception;

use Shopware\Core\Checkout\Cart\Error\Error;

abstract class RentalProductCartChangedException extends Error
{
    abstract public function getName(): string;
}
