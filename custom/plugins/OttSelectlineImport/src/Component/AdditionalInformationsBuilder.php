<?php declare(strict_types=1);

namespace Ott\SelectlineImport\Component;

class AdditionalInformationsBuilder
{
    public static function build(array $addNodes): array
    {
        $additionalInformation = [];

        foreach ($addNodes as $addNode) {
            $additionalInformation[$addNode['SUPPLIER_AID']] = [
                'shortDesc'        => $addNode['ARTICLE_DETAILS']['DESCRIPTION_SHORT'],
                'longDesc'         => $addNode['ARTICLE_DETAILS']['DESCRIPTION_LONG'],
                'ean'              => $addNode['ARTICLE_DETAILS']['EAN'],
                'manufacturerId'   => $addNode['ARTICLE_DETAILS']['MANUFACTURER_AID'],
                'manufacturerName' => $addNode['ARTICLE_DETAILS']['MANUFACTURER_NAME'],
                'additionalText'   => isset($addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][1])
                    ? $addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][1]['FEATURE']['FVALUE']
                    : '',
                'features'         => [],
                'pictures'         => [],
            ];

            if (
                isset($addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][0], $addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][0]['FEATURE'])
            ) {
                if (
                    2 === \count($addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][0]['FEATURE'])
                    && isset($addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][0]['FEATURE']['FNAME'])
                ) {
                    $addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][0]['FEATURE'] = [
                        [
                            'FNAME'  => $addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][0]['FEATURE']['FNAME'],
                            'FVALUE' => $addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][0]['FEATURE']['FVALUE'],
                        ],
                    ];
                }

                foreach ($addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES'][0]['FEATURE'] as $features) {
                    if (
                        false === strpos($features['FNAME'], 'Artikel-Bild')
                        && 0 !== (int) $features['FVALUE']
                    ) {
                        $additionalInformation[$addNode['SUPPLIER_AID']]['features'][] = [
                            'name'  => $features['FNAME'],
                            'value' => $features['FVALUE'],
                        ];
                    } elseif (\in_array($features['FNAME'], ['Artikel-Bild', 'Artikel-Bild-1'])) {
                        $additionalInformation[$addNode['SUPPLIER_AID']]['pictures'][] = $features['FVALUE'];
                    }
                }
            } else {
                if (
                    isset($addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES']['FEATURE']['FNAME'])
                ) {
                    if (false === strpos($addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES']['FEATURE']['FNAME'], 'Artikel-Bild')) {
                        $additionalInformation[$addNode['SUPPLIER_AID']]['features'][] = [
                            'name'  => $addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES']['FEATURE']['FNAME'],
                            'value' => $addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES']['FEATURE']['FVALUE'],
                        ];
                    } elseif (\in_array($addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES']['FEATURE']['FNAME'], ['Artikel-Bild', 'Artikel-Bild-1'])) {
                        $additionalInformation[$addNode['SUPPLIER_AID']]['pictures'][] = $addNode['ARTICLE_DETAILS']['ARTICLE_FEATURES']['FEATURE']['FVALUE'];
                    }
                }
            }
        }

        return $additionalInformation;
    }
}
