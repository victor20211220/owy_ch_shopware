<?php declare(strict_types=1);

namespace Ott\Base\Bootstrap;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class DocumentCreationService extends CreationService
{
    private const DEFAULT_CONFIG = '{"vatId": "XX 111 222 333", "bankBic": "SWSKKEFF", "bankIban": "DE11111222223333344444", "bankName": "Kreissparkasse Münster", "pageSize": "a4", "taxNumber": "000111000", "taxOffice": "Coesfeld", "companyName": "Muster AG", "itemsPerPage": 10, "displayFooter": true, "displayHeader": true, "displayPrices": true, "companyAddress": "Muster AG - Ebbinghoff 10 - 48624 Schöppingen", "pageOrientation": "portrait", "displayLineItems": true, "displayPageCount": true, "executiveDirector": "Max Mustermann", "placeOfFulfillment": "Coesfeld", "placeOfJurisdiction": "Coesfeld", "displayCompanyAddress": true, "diplayLineItemPosition": true, "referencedDocumentType": "invoice"}';
    private EntityRepository $documentTypeRepository;
    private EntityRepository $documentConfigurationRepository;
    private EntityRepository $documentSalesChannelRepository;
    public EntityRepository $languageRepository;

    public function __construct(
        EntityRepository $documentTypeRepository,
        EntityRepository $documentConfigurationRepository,
        EntityRepository $documentSalesChannelRepository,
        EntityRepository $languageRepository
    )
    {
        $this->documentTypeRepository = $documentTypeRepository;
        $this->documentConfigurationRepository = $documentConfigurationRepository;
        $this->documentSalesChannelRepository = $documentSalesChannelRepository;
        $this->languageRepository = $languageRepository;

        parent::__construct();
    }

    public function createDocument(
        string $name,
        array $translations = [],
        bool $isGlobal = true,
        array $salesChannels = [],
        array $config = [],
        array $customFields = []
    ): void
    {
        if ($this->alreadyExists($this->documentTypeRepository, $name)) {
            return;
        }

        $translationsToPersist = $this->prepareTranslations($name, $translations, $customFields);

        $documentType = [
            'technicalName' => $name,
            'translations'  => $translationsToPersist,
        ];

        $result = $this->documentTypeRepository->create([$documentType], $this->context);
        $documentTypeId = $result->getEventByEntityName('document_type')->getIds()[0];
        $documentType['id'] = $documentTypeId;
        $documentBaseConfig = [
            'documentTypeId' => $documentTypeId,
            'documentType'   => $documentType,
            'name'           => $name,
            'filenamePrefix' => $name . '_',
            'config'         => $this->getDocumentConfiguration($config),
            'global'         => $isGlobal,
        ];

        $result = $this->documentConfigurationRepository->create([$documentBaseConfig], $this->context);

        $documentBaseConfigId = $result->getEventByEntityName('document_base_config')->getIds()[0];

        if (empty($salesChannels)) {
            $documentSalesChannel = [
                'documentTypeId'       => $documentTypeId,
                'documentBaseConfigId' => $documentBaseConfigId,
            ];

            $this->documentSalesChannelRepository->create([$documentSalesChannel], $this->context);
        } else {
            $documentSalesChannels = [];
            foreach ($salesChannels as $saleChannel) {
                $documentSalesChannels[] = [
                    'salesChannelId'       => $saleChannel,
                    'documentTypeId'       => $documentTypeId,
                    'documentBaseConfigId' => $documentBaseConfigId,
                ];
            }

            $this->documentSalesChannelRepository->create($documentSalesChannels, $this->context);
        }
    }

    private function getDocumentConfiguration(array $configuration = []): array
    {
        $defaultSampleConfig = json_decode(self::DEFAULT_CONFIG, true);

        return array_merge($defaultSampleConfig, $configuration);
    }
}
