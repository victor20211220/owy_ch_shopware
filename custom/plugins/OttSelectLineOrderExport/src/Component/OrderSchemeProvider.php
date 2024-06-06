<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Component;

class OrderSchemeProvider
{
    public const FILE_NAME_SCHEME = 'selectlinebst_%s.xml';
    public const XML_BODY_SCHEME = <<<'XML'
        <?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
            <bestellungen>
                <bestellung>
                    %s
                    <positionen>
                        %s
                    </positionen>
                </bestellung>
            </bestellungen>
        XML;
    public const ORDER_HEAD_SCHEME = <<<'XML'
            <kopfdaten>
                <BestellNrShop>%s</BestellNrShop>
                <Kundennummer>%s</Kundennummer>
                <Ansprechpartner />
                <Waehrungscode>CHF</Waehrungscode>
                <Belegrabatt>0</Belegrabatt>
                <Liefertermin />
                <Valutadatum />
                <BestellungVom>%s</BestellungVom>
                <ZahlungsbedingungNr />
                <BankbezugNr />
                <Zahlungsreferenz />
                <Email>%s</Email>
                <Telefonnummer>%s</Telefonnummer>
                <Kommission>%s</Kommission>
                <RAanrede>%s</RAanrede>
                <RAtitel>%s</RAtitel>
                <RAvorname>%s</RAvorname>
                <RAname>%s</RAname>
                <RAfirma>%s</RAfirma>
                <RAabteilung>%s</RAabteilung>
                <RAzusatz1>%s</RAzusatz1>
                <RAzusatz2>%s</RAzusatz2>
                <RAzusatz3 />
                <RAstrasse>%s</RAstrasse>
                <RAland>%s</RAland>
                <RAplz>%s</RAplz>
                <RAort>%s</RAort>
                <LAanrede>%s</LAanrede>
                <LAtitel>%s</LAtitel>
                <LAvorname>%s</LAvorname>
                <LAname>%s</LAname>
                <LAfirma>%s</LAfirma>
                <LAabteilung>%s</LAabteilung>
                <LAzusatz1>%s</LAzusatz1>
                <LAzusatz2>%s</LAzusatz2>
                <LAzusatz3 />
                <LAstrasse>%s</LAstrasse>
                <LAland>%s</LAland>
                <LAplz>%s</LAplz>
                <LAort>%s</LAort>
                <AbweichendeLieferadresse>%s</AbweichendeLieferadresse>
                <Auftragsnummer>Bestellung: %s</Auftragsnummer>
            </kopfdaten>
        XML;
    public const POSITION_SCHEME = <<<'XML'
            <position>
                <PositionID>%s</PositionID>
                <Zeilentyp>A</Zeilentyp>
                <ArtikelNr>%s</ArtikelNr>
                <Menge>%s</Menge>
                <Mengeneinheit />
                <Preismenge>1</Preismenge>
                <Rabatt />
                <Einzelpreis />
                <Gesamtpreis />
                <Liefertermin>%s</Liefertermin>
                <Gewicht>%s</Gewicht>
                <Bezeichnung />
                <Zusatz />
                <Langtext />
            </position>
        XML;
}
