<?php declare(strict_types=1);

namespace OwyChTheme;

use Shopware\Core\Framework\Plugin;

use Shopware\Storefront\Framework\ThemeInterface;

use Shopware\Core\Framework\Plugin\Context\InstallContext;

use Shopware\Core\System\CustomField\CustomFieldTypes;

use Doctrine\DBAL\Connection;

class OwyChTheme extends Plugin implements ThemeInterface
{
    public function getThemeConfigPath(): string
    {
        return "theme.json";
    }

    public function install(InstallContext $context): void
    {
        $connection = $this->container->get(Connection::class);

        $catCsFieldCheck = $connection->executeQuery(
            "select count(*) as owy_sub_category FROM custom_field_set WHERE name =  'owy_sub_category'"
        );

        $catCsFieldResults = $catCsFieldCheck->fetch();

        if ($catCsFieldResults["owy_sub_category"] < 1) {
            $customFieldSetRepository = $this->container->get(
                "custom_field_set.repository"
            );

            $customFieldSetRepository->create(
                [
                    [
                        "name" => "owy_sub_category",

                        "config" => [
                            "label" => [
                                "de-DE" => "Sub Category Image",

                                "en-GB" => "Sub Category Image",
                            ],
                        ],

                        "customFields" => [
                            [
                                "name" => "owy_sub_category_img",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Sub Category Image",

                                        "en-GB" => "Sub Category Image",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "1",

                                    "type" => "media",
                                ],
                            ],
                        ],
                        "relations" => [["entityName" => "category"]],
                    ],
                ],
                $context->getContext()
            );
        }

        $pd1CsFieldCheck = $connection->executeQuery(
            "select count(*) as custom_product_detail FROM custom_field_set WHERE name =  'custom_product_detail'"
        );

        $pd1CsFieldResults = $pd1CsFieldCheck->fetch();

        if ($pd1CsFieldResults['custom_product_detail'] < 1) {

            $customFieldSetRepository1 = $this->container->get(
                "custom_field_set.repository"
            );

            $customFieldSetRepository1->create(
                [
                    [
                        "name" => "custom_product_detail",

                        "config" => [
                            "label" => [
                                "de-DE" => "Produktinformationsregisterkarten",

                                "en-GB" => "Product Information Tabs",
                            ],
                        ],

                        "customFields" => [
                            [
                                "name" =>
                                    "custom_product_detail_artikelbeschreibung_heading",

                                "type" => CustomFieldTypes::TEXT,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Artikelbeschreibung Heading",

                                        "en-GB" => "Artikelbeschreibung Heading",
                                    ],

                                    "componentName" => "sw-field",

                                    "customFieldType" => "text",

                                    "customFieldPosition" => "1",

                                    "type" => "text",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_artikelbeschreibung_img1",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Artikelbeschreibung Image 1",

                                        "en-GB" => "Artikelbeschreibung Image 1",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "2",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_artikelbeschreibung_img2",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Artikelbeschreibung Image 2",

                                        "en-GB" => "Artikelbeschreibung Image 2",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "3",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_artikelbeschreibung_img3",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Artikelbeschreibung Image 3",

                                        "en-GB" => "Artikelbeschreibung Image 3",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "4",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_artikelbeschreibung_desc1",

                                "type" => CustomFieldTypes::HTML,

                                "config" => [
                                    "label" => [
                                        "de-DE" =>
                                            "Artikelbeschreibung Description 1",

                                        "en-GB" =>
                                            "Artikelbeschreibung Description 1",
                                    ],

                                    "componentName" => "sw-text-editor",

                                    "customFieldType" => "textEditor",

                                    "customFieldPosition" => "5",

                                    "type" => "textEditor",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_artikelbeschreibung_desc2",

                                "type" => CustomFieldTypes::HTML,

                                "config" => [
                                    "label" => [
                                        "de-DE" =>
                                            "Artikelbeschreibung Description 2",

                                        "en-GB" =>
                                            "Artikelbeschreibung Description 2",
                                    ],

                                    "componentName" => "sw-text-editor",

                                    "customFieldType" => "textEditor",

                                    "customFieldPosition" => "6",

                                    "type" => "textEditor",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_portraitshooting_img",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Portraitshooting Image",

                                        "en-GB" => "Portraitshooting Image",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "7",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_portraitshooting_desc",

                                "type" => CustomFieldTypes::HTML,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Portraitshooting Description",

                                        "en-GB" => "Portraitshooting Description",
                                    ],

                                    "componentName" => "sw-text-editor",

                                    "customFieldType" => "textEditor",

                                    "customFieldPosition" => "8",

                                    "type" => "textEditor",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_aubergewohnliche_desc",

                                "type" => CustomFieldTypes::HTML,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Außergewöhnliche Description",

                                        "en-GB" => "Außergewöhnliche Description",
                                    ],

                                    "componentName" => "sw-text-editor",

                                    "customFieldType" => "textEditor",

                                    "customFieldPosition" => "9",

                                    "type" => "textEditor",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_aubergewohnliche_img",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Außergewöhnliche Image",

                                        "en-GB" => "Außergewöhnliche Image",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "10",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_objektivkonstruktion_title",

                                "type" => CustomFieldTypes::TEXT,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Objektivkonstruktion Title",

                                        "en-GB" => "Objektivkonstruktion Title",
                                    ],

                                    "componentName" => "sw-field",

                                    "customFieldType" => "text",

                                    "customFieldPosition" => "11",

                                    "type" => "text",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_objektivkonstruktion_img",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Objektivkonstruktion Image",

                                        "en-GB" => "Objektivkonstruktion Image",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "12",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_leistungsmerkmale_title",

                                "type" => CustomFieldTypes::TEXT,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Leistungsmerkmale title",

                                        "en-GB" => "Leistungsmerkmale title",
                                    ],

                                    "componentName" => "sw-field",

                                    "customFieldType" => "text",

                                    "customFieldPosition" => "13",

                                    "type" => "text",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_leistungsmerkmale_heading1",

                                "type" => CustomFieldTypes::TEXT,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Leistungsmerkmale Heading 1",

                                        "en-GB" => "Leistungsmerkmale Heading 1",
                                    ],

                                    "componentName" => "sw-field",

                                    "customFieldType" => "text",

                                    "customFieldPosition" => "14",

                                    "type" => "text",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_leistungsmerkmale_headin2",

                                "type" => CustomFieldTypes::TEXT,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Leistungsmerkmale Heading 2",

                                        "en-GB" => "Leistungsmerkmale Heading 2",
                                    ],

                                    "componentName" => "sw-field",

                                    "customFieldType" => "text",

                                    "customFieldPosition" => "15",

                                    "type" => "text",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_leistungsmerkmale_img1",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Leistungsmerkmale  Image 1",

                                        "en-GB" => "Leistungsmerkmale  Image 1",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "16",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" =>
                                    "custom_product_detail_leistungsmerkmale_img2",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Leistungsmerkmale  Image 2",

                                        "en-GB" => "Leistungsmerkmale  Image 2",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "17",

                                    "type" => "media",
                                ],
                            ],
                        ],

                        "relations" => [["entityName" => "product"]],
                    ],
                ],
                $context->getContext()
            );

        }

        $pd2CsFieldCheck = $connection->executeQuery(
            "select count(*) as custom_product_gallery FROM custom_field_set WHERE name =  'custom_product_gallery'"
        );

        $pd2CsFieldResults = $pd2CsFieldCheck->fetch();

        if ($pd2CsFieldResults['custom_product_gallery'] < 1) {

            $customFieldSetRepository2 = $this->container->get(
                "custom_field_set.repository"
            );

            $customFieldSetRepository2->create(
                [
                    [
                        "name" => "custom_product_gallery",

                        "config" => [
                            "label" => [
                                "de-DE" => "Product Gallery",

                                "en-GB" => "Product Gallery",
                            ],
                        ],

                        "customFields" => [
                            [
                                "name" => "custom_product_gallery_title",

                                "type" => CustomFieldTypes::TEXT,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Product gallery title",

                                        "en-GB" => "Product gallery title",
                                    ],

                                    "componentName" => "sw-field",

                                    "customFieldType" => "text",

                                    "customFieldPosition" => "1",

                                    "type" => "text",
                                ],
                            ],

                            [
                                "name" => "custom_product_gallery_img1",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Product gallery 1",

                                        "en-GB" => "Product gallery 1",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "2",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" => "custom_product_gallery_img2",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Product gallery 2",

                                        "en-GB" => "Product gallery 2",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "3",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" => "custom_product_gallery_img3",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Product gallery 3",

                                        "en-GB" => "Product gallery 3",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "4",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" => "custom_product_gallery_img4",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Product gallery 4",

                                        "en-GB" => "Product gallery 4",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "5",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" => "custom_product_gallery_img5",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Product gallery 5",

                                        "en-GB" => "Product gallery 5",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "6",

                                    "type" => "media",
                                ],
                            ],

                            [
                                "name" => "custom_product_gallery_img6",

                                "type" => CustomFieldTypes::MEDIA,

                                "config" => [
                                    "label" => [
                                        "de-DE" => "Product gallery 6",

                                        "en-GB" => "Product gallery 6",
                                    ],

                                    "componentName" => "sw-media-field",

                                    "customFieldType" => "media",

                                    "customFieldPosition" => "7",

                                    "type" => "media",
                                ],
                            ],
                        ],
                        "relations" => [["entityName" => "product"]],
                    ],
                ],
                $context->getContext()
            );

        }

        parent::install($context);
    }
}
