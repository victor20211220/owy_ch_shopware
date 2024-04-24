<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23915;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Version\Aggregate\VersionCommit\VersionCommitDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Version\VersionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\VersionManager;
use Shopware\Core\Framework\DataAbstractionLayer\Write\CloneBehavior;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteResult;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class PatchedVersionManager extends VersionManager
{
    public function __construct(
        private readonly VersionManager $inner,
        private readonly EntitySearcherInterface $entitySearcher,
        private readonly VersionCommitDefinition $versionCommitDefinition,
        private readonly VersionDefinition $versionDefinition
    ) {
    }

    /**
     * @param array<array<string, mixed|null>> $rawData
     *
     * @return array<string, array<EntityWriteResult>>
     */
    public function upsert(EntityDefinition $definition, array $rawData, WriteContext $writeContext): array
    {
        return $this->inner->upsert($definition, $rawData, $writeContext);
    }

    /**
     * @param array<array<string, mixed|null>> $rawData
     *
     * @return array<string, array<EntityWriteResult>>
     */
    public function insert(EntityDefinition $definition, array $rawData, WriteContext $writeContext): array
    {
        return $this->inner->insert($definition, $rawData, $writeContext);
    }

    /**
     * @param array<array<string, mixed|null>> $rawData
     *
     * @return array<string, array<EntityWriteResult>>
     */
    public function update(EntityDefinition $definition, array $rawData, WriteContext $writeContext): array
    {
        return $this->inner->update($definition, $rawData, $writeContext);
    }

    /**
     * @param array<array<string, mixed|null>> $ids
     */
    public function delete(EntityDefinition $definition, array $ids, WriteContext $writeContext): WriteResult
    {
        return $this->inner->delete($definition, $ids, $writeContext);
    }

    public function createVersion(EntityDefinition $definition, string $id, WriteContext $context, ?string $name = null, ?string $versionId = null): string
    {
        return $this->inner->createVersion($definition, $id, $context, $name, $versionId);
    }

    public function merge(string $versionId, WriteContext $writeContext): void
    {
        if (!$this->versionExists($versionId)) {
            throw new VersionNotExistsException($versionId);
        }

        if (!$this->hasCommits($versionId, $writeContext->getContext())) {
            throw new NoCommitsFoundException($versionId);
        }

        $this->inner->merge($versionId, $writeContext);
    }

    /**
     * @return array<string, array<EntityWriteResult>>
     */
    public function clone(
        EntityDefinition $definition,
        string $id,
        string $newId,
        string $versionId,
        WriteContext $context,
        CloneBehavior $behavior
    ): array {
        return $this->inner->clone($definition, $id, $newId, $versionId, $context, $behavior);
    }

    private function hasCommits(string $versionId, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('version_commit.versionId', $versionId));
        $criteria->setLimit(1);

        return $this->entitySearcher->search($this->versionCommitDefinition, $criteria, $context)->getTotal() > 0;
    }

    private function versionExists(string $versionId): bool
    {
        $exists = $this->entitySearcher->search(
            $this->versionDefinition,
            new Criteria([$versionId]),
            Context::createDefaultContext()
        );

        return $exists->has($versionId);
    }
}
