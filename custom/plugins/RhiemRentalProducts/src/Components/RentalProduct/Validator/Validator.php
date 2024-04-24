<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Validator;

abstract class Validator
{
    abstract public function validate(array $params): void;
}
