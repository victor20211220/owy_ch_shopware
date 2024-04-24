<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Installer;

use DateTime;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;

class PayloadUpdater implements RentalProductModeInterface
{
    private readonly Connection $connection;

    private readonly ContainerInterface $container;

    public function __construct(Connection $connection, ContainerInterface $container)
    {
        $this->connection = $connection;
        $this->container = $container;
    }

    public function update(): void
    {
        ini_set('max_execution_time', '120');
        $this->updateRentalProductEntity();
        $this->updateOrderLineItemPayload();
        $this->clearCart();
    }

    public function removeSerializedRentalTimes(): void
    {
        ini_set('max_execution_time', '120');
        $this->removeSerializedRentalTimesFromRentalProduct();
        $this->removeSerializedRentalTimesFromOrderLineItem();
        $this->clearCart();
    }

    private function updateRentalProductEntity(): void
    {
        $sql = 'SELECT LOWER(HEX(id)) as id,blocked_periods, LOWER(HEX(product_id)) as product_id,mode
                FROM rental_product
                WHERE blocked_periods IS NOT NULL AND rental_times IS NULL';
        $result = $this->connection->fetchAllAssociative($sql);
        foreach ($result as $row) {
            $blockedPeriods = json_decode((string) $row['blocked_periods'], true, 512, JSON_THROW_ON_ERROR);
            $rentalTimes = [];
            foreach ($blockedPeriods as $blockedPeriod) {
                $rentalTimes[] = RentalTime::createJson(
                    $row['product_id'],
                    (int) ($blockedPeriod['blocked_quantity']),
                    $blockedPeriod['rhiem_rental_products_rent_start'],
                    $blockedPeriod['rhiem_rental_products_rent_end'],
                    'blocked',
                    self::DAYRENT
                );
            }

            $sql = 'UPDATE rental_product
                    SET rental_times = ?
                    WHERE LOWER(HEX(id)) = ?';
            $this->connection->executeQuery(
                $sql,
                [
                    json_encode($rentalTimes, JSON_THROW_ON_ERROR),
                    $row['id'],
                ],
                [
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                ]
            );
        }
    }

    private function updateOrderLineItemPayload(): void
    {
        $sql = "UPDATE order_line_item 
                SET `type`= 'rentalProduct' 
                WHERE payload LIKE '%rentalProduct%' 
                AND `type` != 'rentalProduct'";

        $this->connection->executeQuery($sql);

        $sql = 'SELECT LOWER(HEX(id)) as id,LOWER(HEX(version_id))as version_id,LOWER(HEX(product_id)) as product_id,quantity, payload 
                FROM order_line_item 
                WHERE `type`= ? AND payload NOT LIKE "%rentalTime%" ';
        $result = $this->connection->fetchAllAssociative($sql, ['rentalProduct']);
        /** @var EntityRepository $orderLineItemRepository */
        $orderLineItemRepository = $this->container->get('order_line_item.repository');
        foreach ($result as $row) {
            $payload = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);

            $rentalProduct = $payload['rentalProduct'];

            $rentalProduct['rentalTime'] = RentalTime::createJson(
                $row['product_id'],
                (int) ($row['quantity']),
                $rentalProduct['rhiem_rental_products_rent_start'],
                $rentalProduct['rhiem_rental_products_rent_end'],
                'order',
                self::DAYRENT
            );

            $payload['rentalProduct'] = $rentalProduct;
            $update['id'] = $row['id'];
            $update['versionId'] = $row['version_id'];
            $update['payload'] = $payload;

            $orderLineItemRepository->update(
                [
                    $update,
                ],
                Context::createDefaultContext()
            );
        }
    }

    private function clearCart(): void
    {
        $sql = "DELETE FROM cart WHERE payload LIKE '%rhiem_rental_products_rent_start%'";
        $this->connection->executeQuery($sql);
    }

    private function removeSerializedRentalTimesFromRentalProduct(): void
    {
        $sql = 'SELECT LOWER(HEX(id)) as id, rental_times, blocked_periods
                FROM rental_product
                WHERE rental_times IS NOT NULL';

        $result = $this->connection->fetchAllAssociative($sql);

        foreach ($result as $row) {
            $rentalTimes = json_decode((string) $row['rental_times'], true, 512, JSON_THROW_ON_ERROR);
            $blockedPeriods = json_decode((string) $row['blocked_periods'], true, 512, JSON_THROW_ON_ERROR);

            foreach ($rentalTimes as $key => $rentalTime) {
                try {
                    $rentalTimeObject = unserialize($rentalTime);
                    $rentalTimeJson = $rentalTimeObject->toJson();

                    $rentalTimeJson['type'] = "block";

                    $rentalTimeStartDate = new DateTime($rentalTimeJson['startDate']);
                    $rentalTimeEndDate = new DateTime($rentalTimeJson['endDate']);

                    foreach ($blockedPeriods as $blockedPeriod) {
                        $blockStartDate = new DateTime($blockedPeriod['rhiem_rental_products_rent_start']);
                        $blockEndDate = new DateTime($blockedPeriod['rhiem_rental_products_rent_end']);

                        if ($blockStartDate->format('Y-m-d') === $rentalTimeStartDate->format('Y-m-d') && $blockEndDate->format('Y-m-d') === $rentalTimeEndDate->format('Y-m-d')) {
                            $rentalTimeJson['comment'] = $blockedPeriod['reason'];
                            break;
                        }
                    }

                    $rentalTimes[$key] = $rentalTimeJson;
                } catch (\Exception $e) {
                    echo $e->getMessage();
                }
            }

            $sql = 'UPDATE rental_product
                    SET rental_times = ?
                    WHERE LOWER(HEX(id)) = ?';

            $this->connection->executeQuery(
                $sql,
                [
                    json_encode($rentalTimes, JSON_THROW_ON_ERROR),
                    $row['id'],
                ],
                [
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                ]
            );

            $this->connection->executeQuery(
                'UPDATE rental_product
                SET blocked_periods = NULL
                WHERE blocked_periods IS NOT NULL'
            );
        }
    }

    private function removeSerializedRentalTimesFromOrderLineItem(): void
    {
        $sql = 'SELECT LOWER(HEX(id)) AS id, LOWER(HEX(version_id)) AS version_id, payload
                FROM order_line_item 
                WHERE `type`= ?';

        $result = $this->connection->fetchAllAssociative($sql, ['rentalProduct']);

        /** @var EntityRepository $orderLineItemRepository */
        $orderLineItemRepository = $this->container->get('order_line_item.repository');

        foreach ($result as $row) {
            $payload = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);

            $rentalProduct = $payload['rentalProduct'];
            $rentalTime = unserialize($rentalProduct['rentalTime']);

            $rentalProduct['rentalTime'] = $rentalTime->toJson();
            $rentalProduct['rentalTime']['type'] = 'rent';
            $payload['rentalProduct'] = $rentalProduct;

            $update['id'] = $row['id'];
            $update['versionId'] = $row['version_id'];
            $update['payload'] = $payload;

            $orderLineItemRepository->update(
                [
                    $update,
                ],
                Context::createDefaultContext()
            );
        }
    }
}
