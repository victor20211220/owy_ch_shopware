<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception;

class RentalProductMinRentTimeNotReachedException extends RentalProductRemoveException
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->message = sprintf('The minimum rent time for product %s is not reached.', $name);

        parent::__construct($this->message);
    }

    public function getParameters(): array
    {
        return ['name' => $this->name, '%field%' => $this->name];
    }

    public function getId(): string
    {
        return $this->getMessageKey() . $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessageKey(): string
    {
        return 'rental-product-min-rent-time-not-reached';
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
