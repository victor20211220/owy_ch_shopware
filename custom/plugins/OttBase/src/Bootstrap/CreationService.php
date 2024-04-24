<?php declare(strict_types=1);

namespace Ott\Base\Bootstrap;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

abstract class CreationService
{
    public EntityRepository $languageRepository;
    protected Context $context;

    public function __construct()
    {
        $this->context = Context::createDefaultContext();
    }

    public function alreadyExists(EntityRepository $entityRepository, string $name): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $criteria->setLimit(1);

        $result = $entityRepository->search($criteria, $this->context);

        return 0 < $result->getTotal();
    }

    public function prepareTranslations(string $name, array $translations, array $customFields): array
    {
        if (empty($translations)) {
            $translations['English'] = ucwords($name);
        }

        if (!isset($customFields['English']) && !isset($customFields['Deutsch'])) {
            $customFields['English'] = $customFields;
        }

        $translationsToPersist = [];
        foreach ($translations as $language => $translation) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', $language));
            $criteria->setLimit(1);
            $result = $this->languageRepository->search($criteria, $this->context);

            if (0 < $result->getTotal()) {
                $translationsToPersist[$result->getEntities()->first()->getId()] = ['name' => $translation, 'customFields' => isset($customFields[$language]) && !empty($customFields[$language]) ? $customFields[$language] : null];
            }
        }

        return $translationsToPersist;
    }
}
