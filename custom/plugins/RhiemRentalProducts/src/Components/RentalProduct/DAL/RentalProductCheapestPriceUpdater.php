<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\DAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Shopware\Core\Content\Product\DataAbstractionLayer\AbstractCheapestPriceQuantitySelector;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceContainer;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Shopware\Core\Framework\Uuid\Uuid;

class RentalProductCheapestPriceUpdater
{
    private readonly Connection $connection;

    private readonly AbstractCheapestPriceQuantitySelector $quantitySelector;

    public function __construct(Connection $connection, AbstractCheapestPriceQuantitySelector $quantitySelector)
    {
        $this->connection = $connection;
        $this->quantitySelector = $quantitySelector;
    }

    public function update(array $parentIds, Context $context): void
    {
        $parentIds = array_unique(array_filter($parentIds));

        if ($parentIds === []) {
            return;
        }

        $all = $this->fetchPrices($parentIds, $context);

        $versionId = Uuid::fromHexToBytes($context->getVersionId());

        RetryableQuery::retryable(
            $this->connection,
            function () use ($parentIds, $versionId): void {
                $this->connection->executeUpdate(
                    'UPDATE rental_product SET cheapest_price = NULL, cheapest_price_accessor = NULL WHERE (id IN (:ids) OR parent_id IN (:ids)) AND version_id = :version',
                    ['ids' => Uuid::fromHexToBytesList($parentIds), 'version' => $versionId],
                    ['ids' => Connection::PARAM_STR_ARRAY]
                );
            }
        );

        $cheapestPrice = new RetryableQuery(
            $this->connection,
            $this->connection->prepare(
                'UPDATE rental_product SET cheapest_price = :price WHERE id = :id AND version_id = :version'
            )
        );

        $accessorQuery = new RetryableQuery(
            $this->connection,
            $this->connection->prepare(
                'UPDATE rental_product SET cheapest_price_accessor = :accessor WHERE id = :id AND version_id = :version'
            )
        );

        foreach ($all as $productId => $prices) {
            $container = new CheapestPriceContainer($prices);

            $cheapestPrice->execute(
                [
                    'price' => serialize($container),
                    'id' => Uuid::fromHexToBytes($productId),
                    'version' => $versionId,
                ]
            );

            $grouped = $this->buildAccessor($container);

            foreach ($grouped as $variantId => $accessor) {
                $accessorQuery->execute(
                    [
                        'accessor' => JsonFieldSerializer::encodeJson($accessor),
                        'id' => Uuid::fromHexToBytes($variantId),
                        'version' => $versionId,
                    ]
                );
            }
        }
    }

    private function buildAccessor(CheapestPriceContainer $container): array
    {
        $formatted = [];
        $rules = $container->getRuleIds();
        $rules[] = 'default';

        foreach ($container->getValue() as $variantId => $prices) {
            $variantPrices = [];
            foreach ($rules as $ruleId) {
                $cheapest = $this->getCheapest($ruleId, $prices);

                $mapped = [];
                foreach ($cheapest['price'] as $price) {
                    $mapped['currency' . $price['currencyId']] = $this->mapPrice($price);
                }

                $variantPrices['rule' . $ruleId] = $mapped;
            }

            $formatted[$variantId] = $variantPrices;
        }

        return $formatted;
    }

    private function getCheapest(?string $ruleId, array $prices): array
    {
        if (isset($prices[$ruleId])) {
            return $prices[$ruleId];
        }

        return $prices['default'];
    }

    private function mapPrice(array $price): array
    {
        $array = ['gross' => $price['gross'], 'net' => $price['net']];

        if (isset($price['listPrice'])) {
            $array['listPrice'] = [
                'gross' => $price['listPrice']['gross'],
                'net' => $price['listPrice']['net'],
            ];
        }

        return $array;
    }

    private function fetchPrices(array $ids, Context $context): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            [
                'LOWER(HEX(IFNULL(rental_product.parent_id, rental_product.id))) as parent_id',
                'LOWER(HEX(rental_product.id)) as variant_id',
                'LOWER(HEX(rental_price.rule_id)) as rule_id',
                'IFNULL(rental_product.active, rental_parent.active) as isRentalPrice',
                'rental_price.price',
                'null as unit_id',
                'null as purchase_unit',
                'null as reference_unit',
                'null as min_purchase',
            ]
        );

        $query->from('rental_product', 'rental_product');
        $query->innerJoin(
            'rental_product',
            'rental_product_price',
            'rental_price',
            'rental_price.rental_product_id = rental_product.prices AND rental_product.version_id = rental_price.rental_product_version_id'
        );
        $query->leftJoin(
            'rental_product',
            'rental_product',
            'rental_parent',
            'rental_parent.id = rental_product.parent_id'
        );

        $query->andWhere('rental_product.id IN (:ids) OR rental_product.parent_id IN (:ids)');
        $query->andWhere('IFNULL(rental_product.active, rental_parent.active) = 1');
        $query->andWhere('(rental_product.child_count = 0 OR rental_product.parent_id IS NOT NULL)');
        $query->andWhere('rental_product.version_id = :version');

        $this->quantitySelector->add($query);

        $ids = Uuid::fromHexToBytesList($ids);

        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $query->setParameter('version', Uuid::fromHexToBytes($context->getVersionId()));

        /** @var Result $execute */
        $execute = $query->executeQuery();
        $data = $execute->fetchAllAssociative();

        $productQuery = $this->connection->createQueryBuilder();
        $productQuery->select(
            [
                'LOWER(HEX(IFNULL(rental_product.parent_id, rental_product.id))) as parent_id',
                'LOWER(HEX(rental_product.id)) as variant_id',
                'LOWER(HEX(product_price.rule_id)) as rule_id',
                'IFNULL(rental_product.active, rental_parent.active) as isRentalPrice',
                'product_price.price',
                'LOWER(HEX(IFNULL(product.unit_id, product_parent.unit_id))) as unit_id',
                'IFNULL(product.purchase_unit, product_parent.purchase_unit) as purchase_unit',
                'IFNULL(product.reference_unit, product_parent.reference_unit) as reference_unit',
                'IFNULL(product.min_purchase, product_parent.min_purchase) as min_purchase',
            ]
        );
        $productQuery->from('rental_product', 'rental_product');

        $productQuery->leftJoin(
            'rental_product',
            'rental_product',
            'rental_parent',
            'rental_parent.id = rental_product.parent_id'
        );

        $productQuery->leftJoin(
            'rental_product',
            'product',
            'product',
            'rental_product.product_id = product.id AND rental_product.product_version_id = product.version_id'
        );
        $productQuery->innerJoin(
            'product',
            'product_price',
            'product_price',
            'product_price.product_id = product.prices AND product.version_id = product_price.product_version_id'
        );
        $productQuery->leftJoin('product', 'product', 'product_parent', 'product_parent.id = product.parent_id');

        $productQuery->andWhere('rental_product.id IN (:ids) OR rental_product.parent_id IN (:ids)');
        $productQuery->andWhere('IFNULL(rental_product.active, rental_parent.active) = 0');
        $productQuery->andWhere('(product.child_count = 0 OR product.parent_id IS NOT NULL)');
        $productQuery->andWhere('rental_product.version_id = :version');

        $productQuery->addSelect(
            [
                'product_price.quantity_start != 1 as is_ranged',
            ]
        );

        $productQuery->andWhere('product_price.quantity_end IS NULL');

        $productQuery->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $productQuery->setParameter('version', Uuid::fromHexToBytes($context->getVersionId()));

        /** @var Result $execute */
        $execute = $query->executeQuery();
        $productData = $execute->fetchAllAssociative();

        if (!empty($productData)) {
            $data = array_merge($data, $productData);
        }

        $grouped = [];
        foreach ($data as $key => &$row) {
            $row['isRentalPrice'] = (bool) $row['isRentalPrice'];
            if (!$row['isRentalPrice']) {
                unset($data[$key]['isRentalPrice']);
            }

            $row['price'] = json_decode((string) $row['price'], true, 512, JSON_THROW_ON_ERROR);
            $row['price'] = $this->normalizePrices($row['price']);
            $grouped[$row['parent_id']][$row['variant_id']][$row['rule_id']] = $row;
        }

        $query = $this->connection->createQueryBuilder();
        $query->select(
            [
                'LOWER(HEX(IFNULL(rental_product.parent_id, rental_product.id))) as parent_id',
                'LOWER(HEX(rental_product.id)) as variant_id',
                'NULL as rule_id',
                '0 AS is_ranged',
                'IFNULL(rental_product.price, rental_parent.price) as price',
                'IFNULL(rental_product.active, rental_parent.active) as isRentalPrice',
                'null as unit_id',
                'null as purchase_unit',
                'null as reference_unit',
                'null as min_purchase',
            ]
        );

        $query->from('rental_product', 'rental_product');
        $query->leftJoin(
            'rental_product',
            'rental_product',
            'rental_parent',
            'rental_product.parent_id = rental_parent.id'
        );

        $query->andWhere('rental_product.id IN (:ids) OR rental_product.parent_id IN (:ids)');
        $query->andWhere('(rental_product.child_count = 0 OR rental_product.parent_id IS NOT NULL)');
        $query->andWhere('rental_product.version_id = :version');
        $query->andWhere('IFNULL(rental_product.active, rental_parent.active) = 1');

        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $query->setParameter('version', Uuid::fromHexToBytes($context->getVersionId()));

        /** @var Result $execute */
        $execute = $query->executeQuery();
        $defaults = $execute->fetchAllAssociative();

        $productQuery = $this->connection->createQueryBuilder();
        $productQuery->select(
            [
                'LOWER(HEX(IFNULL(rental_product.parent_id, rental_product.id))) as parent_id',
                'LOWER(HEX(rental_product.id)) as variant_id',
                'NULL as rule_id',
                '0 AS is_ranged',
                'IFNULL(product.price, product_parent.price) as price',
                'IFNULL(product.min_purchase, product_parent.min_purchase) as min_purchase',
                'LOWER(HEX(IFNULL(product.unit_id, product_parent.unit_id))) as unit_id',
                'IFNULL(product.purchase_unit, product_parent.purchase_unit) as purchase_unit',
                'IFNULL(product.reference_unit, product_parent.reference_unit) as reference_unit',
            ]
        );
        $productQuery->from('rental_product', 'rental_product');
        $productQuery->leftJoin(
            'rental_product',
            'rental_product',
            'rental_parent',
            'rental_product.parent_id = rental_parent.id'
        );
        $productQuery->leftJoin('rental_product', 'product', 'product', 'rental_product.product_id = product.id');
        $productQuery->leftJoin('product', 'product', 'product_parent', 'product.parent_id = product_parent.id');
        $productQuery->andWhere('rental_product.id IN (:ids) OR rental_product.parent_id IN (:ids)');
        $productQuery->andWhere('rental_product.version_id = :version');
        $productQuery->andWhere('IFNULL(rental_product.active, rental_parent.active) = 0');
        $productQuery->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $productQuery->setParameter('version', Uuid::fromHexToBytes($context->getVersionId()));

        /** @var Result $execute */
        $execute = $query->executeQuery();
        $productDefaults = $execute->fetchAllAssociative();

        if (!empty($productDefaults)) {
            $defaults = array_merge($defaults, $productDefaults);
        }

        foreach ($defaults as $row) {
            $row['price'] = json_decode((string) $row['price'], true, 512, JSON_THROW_ON_ERROR);
            $row['price'] = $this->normalizePrices($row['price']);
            $grouped[$row['parent_id']][$row['variant_id']]['default'] = $row;
        }

        return $grouped;
    }

    private function normalizePrices(array $prices): array
    {
        foreach ($prices as &$price) {
            $price['net'] = (float) $price['net'];
            $price['gross'] = (float) $price['gross'];
        }

        return $prices;
    }
}
