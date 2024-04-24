# 3.1.0
- LAN-135 - Behebt einen Fehler der dafür gesorgt hat, dass das Domain-Modal nur nach Löschen von Sprachen geöffnet werden konnte
- LAN-141 - Benutzung von `Swag\LanguagePack\Util\Lifecycle\Lifecycle::deactivate` in `Swag\LanguagePack\SwagLanguagePack.php` entfernt, so dass keine Fehler mehr geworfen werden, wenn das Plugin deaktiviert wird und Sprachen aus diesem noch verwendet werden um Debugging und Updaten zu erleichtern
- LAN-147 - Alle unterstützten Sprachen mit Community-Übersetzungen aktualisiert (Shopware Version 6.5.6.1)
- NEXT-29015 - Deprecated `swag-sales-channel-defaults-select-filterable`. Die Komponente wird mit 3.1.0 entfernt. Alternativ kann `sw-sales-channel-defaults-select` genutzt werden.
 
# 3.0.0
- LAN-131 - Kompatibilität für Shopware 6.5.0.0

# 2.5.0
- LAN-133 - Alle unterstützten Sprachen mit Community-Übersetzungen aktualisiert (Shopware Version 6.4.19.0)
- LAN-133 - `src/Core/Content/PackLanguageRepositoryDecorator.php` als "deprecated" markiert, um es mit 3.0.0 zu entfernen

# 2.4.0
- LAN-119 - Englisch (US) zu den angebotenen Sprachen hinzugefügt & Rumänische Locale von "ro-MD" nach "ro-RO" korrigiert

# 2.3.0
- LAN-90 - Erweiterungsbeschreibung aktualisiert
- LAN-105 - Alle unterstützten Sprachen mit Community-Übersetzungen aktualisiert (Shopware Version 6.4.14.0)
- LAN-112 - Länderflaggen zur Storefront-Sprachauswahl aktualisiert 

# 2.2.0
- LAN-91 - Kompatibilität für Shopware 6.4.6.0
- LAN-91 - Shopware Versionsanforderung auf ~6.4.6 erhöht

# 2.1.2
- NTR - Kompatibilität für Shopware 6.4.5.0

# 2.1.1
- LAN-62 - Behebt einige Fehler im Bezug auf Sprach-Zugriffsrechten
- LAN-82 - Behebt Aufruf des Verkaufskanal-Domain-Modals, wenn der Verkaufskanal nach Hinzufügen einer neuen Sprache nicht zuvor gespeichert wurde
- LAN-83 - Behebt ein Problem, bei dem die Administrationssprache unnötigerweise geändert wurde

# 2.1.0
- LAN-74 - Übersetzungen für Shopware 6.4.1.0 aktualisiert
- LAN-74 - Koreanische Sprache hinzugefügt
- LAN-74 - Griechische Sprache hinzugefügt
- LAN-74 - Ukrainische Sprache hinzugefügt
- LAN-74 - Türkische Sprache hinzugefügt
- LAN-74 - Slowenische Sprache hinzugefügt
- LAN-74 - Slowakische Sprache hinzugefügt
- LAN-74 - Serbisch (Latein) hinzugefügt
- LAN-74 - Hindi wurde hinzugefügt
- LAN-74 - Kroatische Sprache hinzugefügt
- LAN-74 - Bulgarische Sprache hinzugefügt

# 2.0.1
- LAN-67 - Das Plugin ist jetzt valide für den Konsolenbefehl `dal:validate`

# 2.0.0
- LAN-53 - Kompatibilität für Shopware 6.4

# 1.2.1
- LAN-64 - Assoziiere Import existierender Sprachen neu, so dass die Abhängigkeit auf die Übersetzung anstatt wie zuvor auf den Ort verknüpft wird

# 1.2.0
- B2B-459 - Fügt SwagEnterpriseSearch, PluginPublisher and B2bSuite hinzu
- LAN-56 - Deaktivierte Sprachen werden für den Befehl `sales-channel:create:storefront` nun ausgeschlossen

# 1.1.0
- LAN-56 - Der Befehl sales-channel:create verwendet jetzt nur noch die aktivierten Sprachen und die Standardsprachen
- LAN-57 - Sprach-Criteria angepasst, um auch Zusatzsprachen anzuzeigen, die nicht mit diesem Sprachpaket in Verbindung stehen

# 1.0.0
- LAN-20 - Erste Veröffentlichung des Sprachpakets für Shopware 6
