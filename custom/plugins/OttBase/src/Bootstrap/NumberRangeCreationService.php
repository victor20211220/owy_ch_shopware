<?php declare(strict_types=1);

namespace Ott\Base\Bootstrap;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class NumberRangeCreationService extends CreationService
{
    private EntityRepository $numberRangeRepository;
    public EntityRepository $languageRepository;

    public function __construct(
        EntityRepository $numberRangeRepository,
        EntityRepository $languageRepository
    )
    {
        $this->numberRangeRepository = $numberRangeRepository;
        $this->languageRepository = $languageRepository;

        parent::__construct();
    }

    public function createNumberRange(
        string $name,
        string $technicalName = '',
        int $start = 1,
        string $pattern = '{n}',
        array $translations = [],
        bool $isGlobal = true,
        array $customFields = [],
        array $salesChannels = []
    ): void
    {
        if ($this->alreadyExists($this->numberRangeRepository, $name)) {
            return;
        }

        $translationsToPersist = $this->prepareTranslations($name, $translations, $customFields);

        $numberRange = [
            'type' => [
                'technicalName' => $technicalName ?: $name,
                'typeName'      => ucwords($name),
                'global'        => $isGlobal,
            ],
            'pattern'        => $pattern,
            'start'          => $start,
            'translations'   => $translationsToPersist,
            'global'         => $isGlobal,
            'salesChannels'  => $salesChannels,
        ];

        $this->numberRangeRepository->create([$numberRange], $this->context);
    }
}
