<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception;

use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;

class RentalProductRentPeriodInvalidException extends RentalProductRemoveException
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var RentalTime
     */
    protected $rentTime;

    public function __construct(string $id, string $name, RentalTime $rentTime)
    {
        $this->id = $id;
        $this->name = $name;
        $this->rentTime = $rentTime;

        $this->message = sprintf('The rent period for product %s is invalid.', $name);

        parent::__construct($this->message);
    }

    public function getParameters(): array
    {
        return [
            'name' => $this->name,
            'rent_start' => $this->rentTime->getStartDate()->format('d.m.Y'),
            'rent_end' => $this->rentTime->getEndDate()->format('d.m.Y'),
        ];
    }

    public function getId(): string
    {
        return $this->getMessageKey() . $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRentTime(): RentalTime
    {
        return $this->rentTime;
    }

    public function getMessageKey(): string
    {
        return 'rental-product-rent-period-invalid';
    }

    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }
}
