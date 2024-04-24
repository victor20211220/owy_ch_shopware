<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Framework\DataAbstractionLayer\Write;

use Acris\StoreLocator\Core\Framework\DataAbstractionLayer\Exception\CmsPageWithIdNotFound;
use Acris\StoreLocator\Core\Framework\DataAbstractionLayer\Exception\CountryForStoreGroupNotFoundException;
use Acris\StoreLocator\Core\Framework\DataAbstractionLayer\Exception\StoreGroupNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\JsonUpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteCommandExtractor as ParentClass;

/**
 * Builds the command queue for write operations.
 *
 * Contains recursive calls from extract->map->AssociationInterface->extract->map->....
 */
class WriteCommandExtractor extends ParentClass
{
    /**
     * @var ParentClass
     */
    private $writeCommandExtractor;

    /**
     * @var DefinitionInstanceRegistry
     */
    private $definitionRegistry;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ParentClass $writeCommandExtractor,
        DefinitionInstanceRegistry $definitionRegistry,
        ContainerInterface $container
    ) {
        $this->writeCommandExtractor = $writeCommandExtractor;
        $this->definitionRegistry = $definitionRegistry;
        $this->container = $container;
    }

    public function extract(array $rawData, WriteParameterBag $parameters): array
    {
        $context = $parameters->getContext()->getContext();

        $definition = $parameters->getDefinition();

        if($definition->getEntityName() === 'acris_store_locator' && !empty($rawData) && isset($rawData['translations']) && isset($rawData['id'])) {
            $rawData = $this->getStoreLocatorRawData($rawData, $context);
        }

        return $this->writeCommandExtractor->extract($rawData,$parameters);
    }

    public function extractJsonUpdate($data, EntityExistence $existence, WriteParameterBag $parameters): void
    {
        foreach ($data as $storageName => $attributes) {
            $definition = $this->definitionRegistry->getByEntityName($existence->getEntityName());

            $pks = Uuid::fromHexToBytesList($existence->getPrimaryKey());
            $jsonUpdateCommand = new JsonUpdateCommand(
                $definition,
                $storageName,
                $attributes,
                $pks,
                $existence,
                $parameters->getPath()
            );
            $parameters->getCommandQueue()->add($jsonUpdateCommand->getDefinition(), $jsonUpdateCommand);
        }
    }

    private function getStoreLocatorRawData(array $rawData, Context $context): array
    {
        $storeGroupRepository = $this->container->get('acris_store_group.repository');
        if (array_key_exists('storeGroup', $rawData) && !empty($rawData['storeGroup']) && array_key_exists('translations', $rawData['storeGroup']) && !empty($rawData['storeGroup']['translations'])) {
            foreach ($rawData['storeGroup']['translations'] as $storeGroupTranslation) {
                if (!empty($storeGroupTranslation) && array_key_exists('name', $storeGroupTranslation)) {
                    $name = $storeGroupTranslation['name'];
                    $storeGroup = $storeGroupRepository->search((new Criteria())->addFilter(new EqualsFilter('name', $name)), $context)->first();
                    if (!empty($storeGroup)) {
                        $rawData['storeGroupId'] = $storeGroup->getId();
                        unset($rawData['storeGroup']);
                    } else {
                        $storeGroup = $storeGroupRepository->search((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                            new EqualsFilter('default', true),
                            new EqualsFilter('name', 'Standard')
                        ])), $context)->first();
                        if ($storeGroup) {
                            $rawData['storeGroupId'] = $storeGroup->getId();
                            unset($rawData['storeGroup']);
                        } else {
                            throw new StoreGroupNotFoundException("Default store group was not found!");
                        }
                    }
                }
            }
        }
        if (!array_key_exists('storeGroup', $rawData) && !array_key_exists('storeGroupId', $rawData) && array_key_exists('update', $rawData) && !empty($rawData['update']) && $rawData['update'] === true) {
            $storeGroup = $storeGroupRepository->search((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('default', true),
                new EqualsFilter('name', 'Standard')
            ])), $context)->first();
            if ($storeGroup) {
                $rawData['storeGroupId'] = $storeGroup->getId();
            } else {
                throw new StoreGroupNotFoundException("Default store group was not found!");
            }
            unset($rawData['update']);
        }
        if (array_key_exists('cmsPageId', $rawData) && !empty($rawData['cmsPageId'])) {
            $cmsPageId = $rawData['cmsPageId'];
            $cmsPageRepository = $this->container->get('cms_page.repository');
            $cmsPage = $cmsPageRepository->search((new Criteria([$cmsPageId])), $context)->first();

            if (empty($cmsPage)) {
                throw new CmsPageWithIdNotFound($cmsPageId);
            }
        }
        if (array_key_exists('country', $rawData) && !empty($rawData['country']) && array_key_exists('translations', $rawData['country']) && !empty($rawData['country']['translations']) && is_array($rawData['country']['translations'])) {
            foreach ($rawData['country']['translations'] as $country) {
                if (array_key_exists('name', $country) && !empty($country['name'])) {
                    $countryRepository = $this->container->get('country.repository');
                    $multiFilters = [
                        new EqualsFilter('iso', $country['name']),
                        new EqualsFilter('name', $country['name'])
                    ];
                    if (Uuid::isValid($country['name'])) {
                        $multiFilters[] = new EqualsFilter('id', $country['name']);
                    }
                    $countryId = $countryRepository->searchIds((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $multiFilters)), $context)->firstId();

                    if (!empty($countryId)) {
                        $rawData['countryId'] = $countryId;
                        unset($rawData['country']);
                    } else {
                        throw new CountryForStoreGroupNotFoundException($country['name']);
                    }
                }
            }
        }

        return $rawData;
    }
}
