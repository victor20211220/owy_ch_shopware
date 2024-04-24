<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Installer;

use Doctrine\DBAL\Connection;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\DAL\RentalProductCheapestPriceUpdater;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ListingPriceUpdater implements RentalProductModeInterface
{
    private readonly Connection $connection;

    private readonly ContainerInterface $container;

    private readonly Context $context;

    public function __construct(Connection $connection, ContainerInterface $container, Context $context)
    {
        $this->connection = $connection;
        $this->container = $container;
        $this->context = $context;
    }

    public function indexRentalListingPrices(): void
    {
        $sql = 'SELECT LOWER(HEX(id)) as id
                FROM rental_product
                WHERE active = 1';
        $result = $this->connection->fetchAllAssociative($sql);
        if (!empty($result)) {
            $ids = array_column($result, 'id');
            /**
             * @var RentalProductCheapestPriceUpdater $rentalProductCheapestPriceUpdater
             */
            $rentalProductCheapestPriceUpdater = $this->container->get(
                RentalProductCheapestPriceUpdater::class
            );
            $rentalProductCheapestPriceUpdater->update($ids, $this->context);
        }
    }
}
