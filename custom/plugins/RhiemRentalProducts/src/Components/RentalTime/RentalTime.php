<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalTime;

use League\Period\Exception;
use League\Period\Period;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;

class RentalTime implements RentalProductModeInterface
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';
    /**
     * @var string
     */
    private $productId;

    /**
     * @var int
     */
    private $quantity;

    private \DateTime $startDate;

    private \DateTime $endDate;

    /**
     * @var Period
     */
    private $period;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $mode;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @param int    $quantity
     * @param string $startDate
     * @param string $endDate
     * @param string $type
     * @param int    $mode
     * @param string $comment
     * @param string $timezone
     *
     * @throws Exception
     */
    final private function __construct(string $productId, $quantity, $startDate, $endDate, $type, $mode, $comment, $timezone)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $startDate = new \DateTime($startDate, new \DateTimeZone($timezone));
        $endDate = new \DateTime($endDate, new \DateTimeZone($timezone));
        if ($mode === self::DAYRENT) {
            $startDate->setTime(0, 0, 0);
            $endDate->setTime(23, 59, 59);
        }

        $this->period = new Period($startDate, $endDate, Period::INCLUDE_ALL);
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->mode = $mode;
        $this->comment = $comment;
        $this->timezone = $timezone;
    }

    /**
     * @throws Exception
     *
     * @return RentalTime
     */
    public static function createRentalTime(
        string $productId,
        int $quantity,
        string $startDate,
        string $endDate,
        string $type,
        int $mode,
        string $comment = '',
        string $timezone = 'UTC'
    ) {
        return new self(
            $productId,
            $quantity,
            $startDate,
            $endDate,
            $type,
            $mode,
            $comment,
            $timezone
        );
    }

    /**
     * @throws Exception
     *
     * @return RentalTime
     */
    public static function fromJson(array $rentalTime)
    {
        return new self(
            $rentalTime['productId'],
            $rentalTime['quantity'],
            $rentalTime['startDate'],
            $rentalTime['endDate'],
            $rentalTime['type'],
            $rentalTime['mode'],
            $rentalTime['comment'],
            $rentalTime['timezone'],
        );
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public static function createJson(
        string $productId,
        int $quantity,
        string $startDate,
        string $endDate,
        string $type,
        int $mode,
        string $comment = '',
        string $timezone = 'UTC',
    ) {
        return [
            'productId' => $productId,
            'quantity' => $quantity,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'type' => $type,
            'mode' => $mode,
            'comment' => $comment,
            'timezone' => $timezone
        ];
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function toJson()
    {
        return [
            'productId' => $this->productId,
            'quantity' => $this->quantity,
            'startDate' => $this->startDate->format(self::DATE_FORMAT),
            'endDate' => $this->endDate->format(self::DATE_FORMAT),
            'type' => $this->type,
            'mode' => $this->mode,
            'comment' => $this->comment,
            'timezone' => $this->timezone,
        ];
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getPeriod(): Period
    {
        return $this->period;
    }

    public function setPeriod(Period $period): void
    {
        $this->period = $period;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function setMode($mode): void
    {
        $this->mode = $mode;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment): void
    {
        $this->comment = $comment;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setTimezone($timezone): void
    {
        $this->timezone = $timezone;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function setProductId($productId): void
    {
        $this->productId = $productId;
    }
}
