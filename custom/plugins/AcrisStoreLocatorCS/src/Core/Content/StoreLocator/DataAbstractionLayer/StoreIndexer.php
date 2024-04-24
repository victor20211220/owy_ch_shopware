<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\StoreLocator\DataAbstractionLayer;

use Acris\StoreLocator\Custom\StoreLocatorDefinition;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\InheritanceUpdater;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StoreIndexer extends EntityIndexer
{
    public function __construct(
       private readonly IteratorFactory $iteratorFactory,
       private readonly EntityRepository $repository,
       private readonly Connection $connection,
       private readonly InheritanceUpdater $inheritanceUpdater,
       private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getName(): string
    {
        return 'acris_store_locator.indexer';
    }

    /**
     * @param array|null $offset
     */
    public function iterate($offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator($this->repository->getDefinition(), $offset);

        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new StoreIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $updates = $event->getPrimaryKeys(StoreLocatorDefinition::ENTITY_NAME);

        if (empty($updates)) {
            return null;
        }

        $this->inheritanceUpdater->update(StoreLocatorDefinition::ENTITY_NAME, $updates, $event->getContext());

        return new StoreIndexingMessage(array_values($updates), null, $event->getContext());
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $ids = $message->getData();
        $ids = array_unique(array_filter($ids));

        if (empty($ids)) {
            return;
        }

        $context = $message->getContext();

        $this->connection->beginTransaction();

        $this->connection->executeStatement(
            'UPDATE acris_store_locator SET updated_at = :now WHERE id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($ids), 'now' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
            ['ids' => Connection::PARAM_STR_ARRAY]
        );
        $this->connection->commit();

        $this->eventDispatcher->dispatch(new StoreIndexerEvent($ids, $context));
    }

    public function getTotal(): int
    {
        // TODO: Implement getTotal() method.
        return $this->iteratorFactory->createIterator($this->repository->getDefinition())->fetchCount();
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(static::class);
    }
}
