<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Command;

use Ott\Base\Command\SingletonCommand;
use Ott\Base\FileHelper\CsvHelper;
use Ott\Base\FileHelper\XmlHelper;
use Ott\SelectlineImport\Component\CategoryTreeBuilder;
use Ott\SelectlineImport\Service\ImportMessageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseFileCommand extends SingletonCommand
{
    private const UPLOAD_DIR_BASE = __DIR__ . '/../Resources/transfer/import/';
    private const LOCAL_IMAGE_DIR_BASE = __DIR__ . '/../Resources/transfer/images/';
    private const PRODUCT_FILE = 'artikel.xml';
    private const PROPERTY_FILE = 'properties.csv';
    private const CATEGORY_FILE = 'kategorien.xml';
    private const CUSTOMER_FILE = 'kundenstamm.xml';
    private const DISCOUNT_FILE = 'rabattgruppen.xml';
    private const TYPES = ['product'];
    private ?array $propertyMapper = null;
    private ImportMessageManager $importMessageManager;
    private CsvHelper $csvParser;

    public function __construct(ImportMessageManager $importMessageManager, CsvHelper $csvParser)
    {
        parent::__construct();

        $this->importMessageManager = $importMessageManager;
        $this->csvParser = $csvParser;
    }

    public function configure(): void
    {
        $this->setName('ott:import:file');
        $this->addArgument('type');
        $this->addOption('customer-only', 'c', InputOption::VALUE_NONE);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->hasLockFile()) {
            $output->writeln('Command already running');

            return Command::SUCCESS;
        }

        $this->lockProcess();

        $inputType = $input->getArgument('type');

        $isCustomerImportOnly = (bool) $input->getOption('customer-only');
        $importDate = date('Y-m-d H:i:s', time());
        $xmlHelper = new XmlHelper();

        foreach (static::TYPES as $type) {
            if ($inputType && $inputType !== $type) {
                continue;
            }

            if ($isCustomerImportOnly) {
                continue;
            }

            $output->write('Reading category xml ........');
            $categoryNodes = $xmlHelper->getXmlAsArray(static::UPLOAD_DIR_BASE . static::CATEGORY_FILE);
            $output->writeln('Ok');
            $output->write('Prepare category tree .......');
            $categoryTree = CategoryTreeBuilder::buildTree($categoryNodes['Artikelgruppe']);
            unset($categoryNodes);
            $output->writeln('Ok');
            $nodes = [['depth' => 1, 'name' => 'Artikel']];
            $productNumber = '';
            $productFile = static::UPLOAD_DIR_BASE . static::PRODUCT_FILE;
            $productCount = $xmlHelper->getDataCount($productFile, $nodes);
            $xmlHelper->openFile($productFile);

            if (null === $this->propertyMapper) {
                $this->csvParser->removeUtf8Bom(static::UPLOAD_DIR_BASE . static::PROPERTY_FILE);
                $this->propertyMapper = $this->csvParser->getCsvAsArray(static::UPLOAD_DIR_BASE . static::PROPERTY_FILE);
            }

            $discountGroups = $xmlHelper->getXmlAsArray(static::UPLOAD_DIR_BASE . static::DISCOUNT_FILE);

            $productDiscountGroups = [];
            foreach ($discountGroups['Rabattgruppe'] as $discountGroup) {
                foreach ($discountGroup['Positionen']['Position'] as $discountPositions) {
                    $productDiscountGroups[$discountPositions['Nummer']]['discount' . $discountGroup['Nummer']] = $discountPositions['Rabatt'];
                }
            }

            $progress = new ProgressBar($output);
            $progress->start($productCount);
            $this->importMessageManager
                ->setFile($productFile)
                ->setType($type)
            ;
            while (null !== $item = $xmlHelper->next($nodes)) {
                $productNumber = (string) $item->Artikelnummer ?? '';

                $pictures = [];
                $pictureLink = (array) $item->BilderLinks;
                if (isset($pictureLink['BilderLink'])) {
                    if (\is_string($pictureLink['BilderLink'])) {
                        $pictures[] = $pictureLink['BilderLink'];
                    } else {
                        foreach ($pictureLink['BilderLink'] as $imageLink) {
                            $pictures[] = $imageLink;
                        }
                    }
                } else {
                    $i = 0;
                    while (19 >= $i) {
                        $imageLink = self::LOCAL_IMAGE_DIR_BASE
                            . 'B_' . $productNumber
                            . '_' . $i . '.jpg';

                        if (
                            file_exists($imageLink)
                        ) {
                            $pictures[] = $imageLink;
                        }
                        ++$i;
                    }
                }

                $features = [];
                foreach ($this->propertyMapper as $mapper) {
                    if (isset($mapper['techname']) && null !== $mapper['techname']) {
                        $techName = strtoupper($mapper['techname']);
                    }

                    if ('' !== (string) $item->{$techName}) {
                        $features[] = [
                            'name'  => $mapper['shopname'],
                            'value' => (string) $item->{$techName},
                        ];
                    }
                }

                $data = [
                    'longDescription'        => (string) $item->LangtextD ?? '',
                    'name'                   => (string) $item->BezeichnungD ?? '',
                    'longDescriptionFrench'  => (string) $item->LangtextF ?? '',
                    'nameFrench'             => (string) $item->BezeichnungF ?? '',
                    'longDescriptionEnglish' => (string) $item->LangtextE ?? '',
                    'nameEnglish'            => (string) $item->BezeichnungE ?? '',
                    'shortDescription'       => '',
                    'nameAdd'                => (string) $item->ZusatzD ?? '',
                    'ean'                    => (string) $item->EANNummer ?? '',
                    'productNumber'          => $productNumber,
                    'categories'             => (array) CategoryTreeBuilder::getCategoryPath(
                        $categoryTree,
                        [(string) $item->ARTIKELGRUPPEONLINESHOP, (string) $item->ARTIKELGRUPPEONLINESHOP2]
                    ),
                    'manufacturerNumber'  => (string) $item->HSTArtikelnummer ?? '',
                    'manufacturer'        => (string) $item->MANUFACTURER ?? 'nicht zugeordnet',
                    'tax'                 => (float) $item->Steuersatz ?? 8.1,
                    'listprice'           => (float) $item->Listenpreis ?? 0,
                    'salesFromDate'       => (string) $item->vonDatumA ?? '',
                    'salesToDate'         => (string) $item->bisDatumA ?? '',
                    'price1'              => (float) $item->Preis1 ?? (float) $item->Listenpreis, //b2b
                    'price4'              => (float) $item->Preis4 ?? (float) $item->Listenpreis, //b2c
                    'price8'              => (float) $item->Preis8 ?? (float) $item->Listenpreis, //b2b sale
                    'price9'              => (float) $item->Preis9 ?? (float) $item->Listenpreis, //b2s sale
                    'isSale'              => (int) ('true' === (string) $item->SCHNAEPPCHENAKTIV ? 1 : 0),
                    'salesprice'          => (float) $item->Aktionspreis ?? 0,
                    'stock'               => (int) ($item->Lagerbestand ?? 1),
                    'customerPrices'      => (array) $item->Kundenpreise,
                    'pictures'            => $pictures,
                    'features'            => $features,
                    'weight'              => (float) $item->Gewicht,
                    'marker'              => [
                        'text'            => (string) $item->MARKER ?? '',
                        'color'           => (string) $item->MARKERFARBE1 ?? '',
                        'color2'          => (string) $item->MARKERFARBE2 ?? '',
                    ],
                    'textLikeProduct'     => (string) $item->LANGTEXTWIEARTIKEL ?? '',
                    'importDate'          => $importDate,
                ];

                $shortNumber = substr((string) $item->Artikelnummer, 0, 2);
                if (isset($productDiscountGroups[$shortNumber])) {
                    $data['discount'] = $productDiscountGroups[$shortNumber];
                }

                $data = json_decode(str_replace('ß', 'ss', json_encode($data)), true);
                $this->importMessageManager->generate($data);
                $progress->advance();
            }
            $xmlHelper->close();
            $progress->finish();
            $output->writeln('');
        }

        return Command::SUCCESS;
    }
}
