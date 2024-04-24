<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception;

class RentalProductNotBuyableException extends RentalProductRemoveException
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
        $this->message = sprintf('The product %s can only be rented.', $name);
        $this->name = $name;

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
        return 'rental-product-not-buyable';
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
