<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\DAL;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\ChildCountUpdater;
use Rhiem\RhiemRentalProducts\Entities\RentalProduct\RentalProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\InheritanceUpdater;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;

class RentalProductIndexer extends EntityIndexer
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var IteratorFactory
     */
    protected $iteratorFactory;

    /**
     * @var RentalProductCheapestPriceUpdater
     */
    protected $rentalProductCheapestPriceUpdater;

    private readonly ChildCountUpdater $childCountUpdater;

    private readonly EntityRepository $rentalProductRepository;

    private readonly InheritanceUpdater $inheritanceUpdater;

    public function __construct(
        ChildCountUpdater $childCountUpdater,
        Connection $connection,
        IteratorFactory $iteratorFactory,
        EntityRepository $rentalProductRepository,
        RentalProductCheapestPriceUpdater $rentalProductCheapestPriceUpdater,
        InheritanceUpdater $inheritanceUpdater
    ) {
        $this->childCountUpdater = $childCountUpdater;
        $this->connection = $connection;
        $this->iteratorFactory = $iteratorFactory;
        $this->rentalProductRepository = $rentalProductRepository;
        $this->rentalProductCheapestPriceUpdater = $rentalProductCheapestPriceUpdater;
        $this->inheritanceUpdater = $inheritanceUpdater;
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(self::class);
    }

    public function getTotal(): int
    {
        return $this->iteratorFactory->createIterator($this->rentalProductRepository->getDefinition())->fetchCount();
    }

    public function getName(): string
    {
        return 'rentalProduct.indexer';
    }

    public function iterate(?array $offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator($this->rentalProductRepository->getDefinition(), $offset);

        $ids = $iterator->fetch();

        if ($ids === []) {
            return null;
        }

        return new RentalProductIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $updates = [];
        $updates['ids'] = $event->getPrimaryKeys(RentalProductDefinition::ENTITY_NAME);

        if (empty($updates['ids'])) {
            return null;
        }

        $this->inheritanceUpdater->update(RentalProductDefinition::ENTITY_NAME, $updates['ids'], $event->getContext());

        return new RentalProductIndexingMessage($updates['ids'], null, $event->getContext());
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $data = $message->getData();

        $ids = array_unique(array_filter($data));

        if ($ids === []) {
            return;
        }

        $context = $message->getContext();

        $parentIds = $this->getParentIds($ids);
        $childrenIds = $this->getChildrenIds($ids);

        $this->inheritanceUpdater->update(
            RentalProductDefinition::ENTITY_NAME,
            array_merge($ids, $parentIds, $childrenIds),
            $context
        );

        $this->childCountUpdater->update(RentalProductDefinition::ENTITY_NAME, $parentIds, $context);

        $this->rentalProductCheapestPriceUpdater->update($parentIds, $context);
    }

    /**
     * @return array|mixed[]
     */
    private function getParentIds(array $ids): array
    {
        $parentIds = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT LOWER(HEX(IFNULL(rental_product.parent_id, id))) as id FROM rental_product WHERE id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($ids)],
            ['ids' => Connection::PARAM_STR_ARRAY]
        );

        return array_unique(array_filter(array_column($parentIds, 'id')));
    }

    private function getChildrenIds(array $ids): array
    {
        $childrenIds = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT LOWER(HEX(id)) as id FROM rental_product WHERE parent_id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($ids)],
            ['ids' => Connection::PARAM_STR_ARRAY]
        );

        return array_unique(array_filter(array_column($childrenIds, 'id')));
    }
}
