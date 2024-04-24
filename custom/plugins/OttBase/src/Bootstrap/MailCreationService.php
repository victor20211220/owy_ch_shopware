<?php declare(strict_types=1);

namespace Ott\Base\Bootstrap;

use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class MailCreationService extends CreationService
{
    private EntityRepository $mailTemplateRepository;
    private EntityRepository $salesChannelRepository;
    private EntityRepository $mailTemplateTypeRepository;
    private EntityRepository $salesChannelTypeRepository;
    public EntityRepository $languageRepository;

    public function __construct(
        EntityRepository $mailTemplateRepository,
        EntityRepository $mailTemplateTypeRepository,
        EntityRepository $salesChannelRepository,
        EntityRepository $salesChannelTypeRepository,
        EntityRepository $languageRepository
    )
    {
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->salesChannelTypeRepository = $salesChannelTypeRepository;
        $this->languageRepository = $languageRepository;

        parent::__construct();
    }

    public function createMail(
        string $name,
        string $technicalName,
        array $availableEntities,
        array $customFields = [],
        array $translations = [],
        array $salesChannels = [],
        bool $systemDefault = false
    ): void
    {
        if ($this->alreadyExists($this->mailTemplateTypeRepository, $technicalName)) {
            return;
        }

        $mailTemplateTypeId = Uuid::randomHex();

        if (empty($salesChannels)) {
            $entityCollection = $this->getBuildingShopSalesChannels();
            foreach ($entityCollection as $salesChannel) {
                $salesChannels[] = ['salesChannelId' => $salesChannel->getId(), 'mailTemplateTypeId' => $mailTemplateTypeId];
            }
        }

        $mailTemplate = [
            'mailTemplateType' => [
                'id'                => $mailTemplateTypeId,
                'name'              => $name,
                'technicalName'     => $technicalName,
                'availableEntities' => $availableEntities,
                'translations'      => $this->prepareTranslations($name, [], []),
            ],
            'salesChannels'     => $salesChannels,
            'isSystemDefault'   => $systemDefault,
            'customFields'      => $customFields,
            'translations'      => $translations,
        ];

        $this->mailTemplateRepository->create([$mailTemplate], $this->context);
    }

    private function getBuildingShopSalesChannels(): ?EntityCollection
    {
        $result = $this->salesChannelTypeRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('iconName', 'regular-storefront')),
            $this->context
        );
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('typeId', $result->getEntities()->first()->getId()))
            ->addAssociations(['language'])
        ;
        $result = $this->salesChannelRepository->search($criteria, $this->context);

        if (0 === $result->getTotal()) {
            return null;
        }

        return $result->getEntities();
    }

    public function alreadyExists($repository, string $name): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $name));
        $criteria->setLimit(1);

        $result = $repository->search($criteria, $this->context);

        return 0 < $result->getTotal();
    }

    public function getMailTemplate(string $technicalName, Context $context): ?MailTemplateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);

        return $this->mailTemplateRepository->search($criteria, $context)->first();
    }
}
