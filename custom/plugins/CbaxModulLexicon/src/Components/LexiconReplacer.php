<?php declare(strict_types = 1);

namespace Cbax\ModulLexicon\Components;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class LexiconReplacer
{
    public function __construct(
        private readonly LexiconSeo $lexiconSeo,
        private readonly LexiconHelper $lexiconHelper)
    {

    }

    public function getReplaceText(
        ?string $text,
        string $salesChannelId,
        string $shopUrl,
        Context $context,
        SalesChannelContext $salesChannelContext,
        array $config,
        ?string $lexicon_id = null
    ): string
    {
        if (empty($text)) {
            return '';
        }

        if (empty($config['active'])) {
            return $text;
        }

        $lexiconEntry = $this->lexiconHelper->getAllLexiconEntries($context, $salesChannelId);

        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
        // Nur ganze Wörter ersetzen (\w ersetzt [a-zäöüß-])
        $replaceMode = ($config['replaceComplete']) ? '\w' : '\b\B';
        // keine Buchstaben, Umlaute oder ß als vorheriges Zeichen, kein <a ...> Tag vorne
        $regExPrefix = "/(?!<a[^>]*>)(?<!$replaceMode)(";
        // nicht innerhalb HTML Tags (<>) und keine Buchstaben, Umlaute oder ß als nachfolgendes Zeichen, kein </a> Tag hinten
        $regExSuffix = ")(?![^<]*>)(?![^<]*<\/a>)(?!$replaceMode)/";
        // /umi (u = utf8 support; m = multiline; i = case insensitive)
        $regExFlags = 'umi';

        if (!empty($lexiconEntry)) {
            //$keywords = [];

            // Alle SEO-URLs holen
            if ($config['linkHandling'] == 'modal') {
                $seoUrls = $this->lexiconSeo->getSeoUrls('frontend.cbax.lexicon.detail', $context, $salesChannelContext);
            } else {
                $seoUrls = [];
            }

            // Suchmuster und Ersetzungen erstellen
            $suchmuster = [];
            $ersetzungen = [];

            foreach ($lexiconEntry as $entry) {
                // den eigenen Eintrag in der Detailseite überspringen und nur andere Einträge prüfen
                if ($lexicon_id !== null && $entry->getId() === $lexicon_id) {
                    continue;
                }

                // Bei Eingabe mehrerer Keywords in einem Feld diese splitten und Array neu erstellen
                $getKeywords = explode('+',  $entry->getTranslated()['keyword']);

                if (count($getKeywords) > 1) {
                    foreach ($getKeywords as $keyword) {
                        if (!empty($keyword)) {
                            $suchmuster[] = $regExPrefix . preg_quote(trim($keyword), '/') . $regExSuffix . $regExFlags;
                            $ersetzungen[] = $this->getReplacement($shopUrl, $config, $entry->get('id'), trim($keyword), htmlspecialchars(strip_tags((string)$entry->getTranslated()['description'])), $seoUrls);
                        }
                    }
                } else {

                    $suchmuster[] = $regExPrefix . preg_quote(trim($entry->getTranslated()['keyword']), '/') . $regExSuffix . $regExFlags;
                    $ersetzungen[] = $this->getReplacement($shopUrl, $config, $entry->get('id'), trim($entry->getTranslated()['keyword']), htmlspecialchars(strip_tags((string)$entry->getTranslated()['description'])), $seoUrls);
                }
            }

            /*
            foreach ($keywords as $keyword) {

                if ($config['linkHandling'] == 'modal') {
                    $path_info = '/cbax/lexicon/detail/' . $keyword["id"];

                    if (isset($seoUrls[$path_info]))
                    {
                        $link = $shopUrl .'/'. $seoUrls[$path_info];
                    }
                    else
                    {
                        $link = $shopUrl . $path_info;
                    }
                }

                $suchmuster[] = $regExPrefix . preg_quote($keyword["keyword"], '/') . $regExSuffix . $regExFlags;

                // $0 ersetzt alle gefundenen Werte
                if ($config['linkHandling'] == 'tooltip') {
                    $dataTemplate = "<div class='tooltip' role='tooltip'><div class='arrow'></div><div class='tooltip-inner cbax-lexicon-tooltip-inner'></div></div>";

                    /*
                    $dataDelay = "'{" . ' <do-not-change><"show": 200, "hide": 2000></do-not-change>' . " }'";
                    $readMore = $this->tranlator->trans('cbaxLexicon.page.boxKeyword.moreRead') . ' ...';

                    // < > um Text stellt sicher, dass dort keine Ersetzung erfolgt
                    // <do-not-change> markiert < um es nach Ersetzung wieder sicher löschen zu können
                    if ($config['showFooter']) {
                        $tooltip = "<p><do-not-change><" . $keyword['description'] . "></do-not-change></p><a href='" . $link . "' title='" . $keyword['keyword'] .
                                "' class='cbax-tooltip-link'><b><do-not-change><" . $readMore . "></do-not-change></b></a>";

                    } else {
                        $tooltip = "<do-not-change><" . $keyword['description'] . "></do-not-change>";
                    }

                    $ersetzungen[] = '<a title=></do-not-change>"' . $tooltip .
                        '" <do-not-change><data-delay=></do-not-change>' .
                        $dataDelay . ' <do-not-change><data-template=></do-not-change>"' .
                        $dataTemplate .
                        '" <do-not-change><data-toggle="tooltip" data-placement="top" class="lexicon-tooltip cbax-lexicon-link" data-html="true">$0</a>';
                    *
                    */

            /*
                    $ersetzungen[] = '<a class="lexicon-tooltip cbax-lexicon-link" data-toggle="tooltip" data-original-title="' .
                            $keyword['description'] .
                            '" data-placement="top" data-html="true" data-template=></do-not-change>"' .
                            $dataTemplate . '">$0</a>';

                } else {
                    $ersetzungen[] = '<span class="lexicon-modal"><a href="' . $link . '" title="' . $keyword["keyword"] . '"
                            data-toggle="modal" data-original-title="' . $keyword['description'] . '" data-cbax-lexicon-url="' . $link . '">$0</a></span>';
                }
            }
            */

            // Einträge ersetzen
            $text1 = preg_replace($suchmuster, $ersetzungen, $text, !empty($config['replaceRepeat']) ? -1 : 1);
            // Marker wieder löschen
            //$text2 = str_replace('<do-not-change><', '', $text1);
            $text = str_replace('></do-not-change>', '', $text1);
        }

        return $text;
    }

    private function getReplacement(
        string $shopUrl,
        array $config,
        string $id,
        string $keyword,
        string $description,
        array $seoUrls
    ): string
    {
        if ($config['linkHandling'] == 'modal') {
            $path_info = '/cbax/lexicon/detail/' . $id;
            //modal controller
            $dataUrl = $shopUrl . '/cbax/lexicon/modalInfo/' . $id;

            if (isset($seoUrls[$path_info]))
            {
                $link = $shopUrl .'/'. $seoUrls[$path_info];
            }
            else
            {
                $link = $shopUrl . $path_info;
            }
        }

        // $0 ersetzt alle gefundenen Werte
        if ($config['linkHandling'] == 'tooltip') {
            $dataTemplate = "<div class='tooltip' role='tooltip'><div class='arrow'></div><div class='tooltip-inner cbax-lexicon-tooltip-inner'></div></div>";

            /*
            $dataDelay = "'{" . ' <do-not-change><"show": 200, "hide": 2000></do-not-change>' . " }'";
            $readMore = $this->tranlator->trans('cbaxLexicon.page.boxKeyword.moreRead') . ' ...';

            // < > um Text stellt sicher, dass dort keine Ersetzung erfolgt
            // <do-not-change> markiert < um es nach Ersetzung wieder sicher löschen zu können
            if ($config['showFooter']) {
                $tooltip = "<p><do-not-change><" . $description . "></do-not-change></p><a href='" . $link . "' title='" . $keyword .
                        "' class='cbax-tooltip-link'><b><do-not-change><" . $readMore . "></do-not-change></b></a>";

            } else {
                $tooltip = "<do-not-change><" . $description . "></do-not-change>";
            }

            $ersetzung = '<a title=></do-not-change>"' . $tooltip .
                '" <do-not-change><data-delay=></do-not-change>' .
                $dataDelay . ' <do-not-change><data-template=></do-not-change>"' .
                $dataTemplate .
                '" <do-not-change><data-toggle="tooltip" data-placement="top" class="lexicon-tooltip cbax-lexicon-link" data-html="true">$0</a>';
            *
            */

            $ersetzung = '<a class="lexicon-tooltip cbax-lexicon-link" data-bs-toggle="tooltip" title="' .
                $description .
                '" data-bs-placement="top" data-bs-html="true" data-bs-template=></do-not-change>"' .
                $dataTemplate . '">$0</a>';

        } else {
            $ersetzung = '<span class="lexicon-modal"><a href="' . $link . '" title="' . $keyword . '"
                            data-ajax-modal="modal" data-original-title="' . $description . '" data-url="' . $dataUrl . '">$0</a></span>';
        }

        return $ersetzung;
    }

}
