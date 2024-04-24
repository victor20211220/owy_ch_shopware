<?php declare(strict_types=1);

namespace Ott\Base\Bootstrap;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Plugin\Context\InstallContext;

class CustomFieldService
{
    private EntityRepository $customFieldSetRepository;
    private EntityRepository $customFieldRepository;
    private array $fieldSets = [];
    private array $fields = [];

    public function __construct(EntityRepository $customFieldSetRepository, EntityRepository $customFieldRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
        $this->customFieldRepository = $customFieldRepository;
    }

    public function addFieldSet(string $name, array $labels = [], array $relations = []): void
    {
        $this->fieldSets[] = ['name' => $name, 'labels' => $labels, 'relations' => $relations];
    }

    public function addField(string $fieldSetName, array $field): void
    {
        if (!isset($this->fields[$fieldSetName])) {
            $this->fields[$fieldSetName] = [];
        }

        $this->fields[$fieldSetName][] = $field;
    }

    public function update(InstallContext $installContext): void
    {
        $fieldSets = [];
        foreach ($this->fieldSets as $fieldSet) {
            $fields = [];
            foreach ($this->fields[$fieldSet['name']] as $field) {
                $fieldNameLegacy = sprintf('custom_%s_%s', $fieldSet['name'], $field['name']);
                $field['name'] = sprintf('ott_%s_%s', $fieldSet['name'], $field['name']);
                if (!isset($field['allowCartExpose'])) {
                    $field['allowCartExpose'] = true;
                }

                $criteria = new Criteria();
                $criteria->addFilter(new MultiFilter('OR', [
                    new EqualsFilter('name', $field['name']),
                    new EqualsFilter('name', $fieldNameLegacy),
                ]))
                    ->setLimit(1)
                    ->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE)
                ;

                $result = $this->customFieldRepository->search($criteria, Context::createDefaultContext());
                if (0 < $result->getTotal()) {
                    continue;
                }

                $fields[] = $field;
            }

            $fieldSetData = [
                'name'         => $fieldSet['name'],
                'config'       => [
                    'label' => $fieldSet['labels'],
                ],
                'customFields' => $fields,
                'relations'    => $fieldSet['relations'],
            ];

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', $fieldSet['name']));
            $criteria->setLimit(1);

            $result = $this->customFieldSetRepository->search($criteria, Context::createDefaultContext());

            if (0 < $result->getTotal()) {
                $entity = $result->getEntities()->first();
                $fieldSetData['id'] = $entity->getId();
                unset($fieldSetData['relations']);
            }

            $fieldSets[] = $fieldSetData;
        }

        $this->customFieldSetRepository->upsert($fieldSets, $installContext->getContext());
    }
}
