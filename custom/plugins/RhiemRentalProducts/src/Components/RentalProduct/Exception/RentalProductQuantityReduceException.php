<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception;

use Shopware\Core\Checkout\Cart\Error\Error;

 class RentalProductQuantityReduceException extends Error
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
         $this->message = sprintf('Your selected quantity for product %s is not completly available.', $name);

         parent::__construct($this->message);
     }

     public function getParameters(): array
     {
         return ['name' => $this->name];
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
         return 'rental-product-quantity-reduce';
     }

     public function getLevel(): int
     {
         return self::LEVEL_WARNING;
     }

     public function blockOrder(): bool
     {
         return false;
     }
 }
