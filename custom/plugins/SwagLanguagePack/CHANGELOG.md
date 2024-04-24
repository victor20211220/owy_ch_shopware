# 3.1.0
- LAN-135 - Fixed an error, where the domain modal only could be opened, when languages were deleted
- LAN-141 - Removed usage of `Swag\LanguagePack\Util\Lifecycle\Lifecycle::deactivate` in `Swag\LanguagePack\SwagLanguagePack.php` to not throw errors, when deinstalling the plugin with languages still in use to enable easier debugging and updating
- LAN-147 - All supported languages got updated by community translations (Shopware version 6.5.6.1)
- NEXT-29015 - Deprecated `swag-sales-channel-defaults-select-filterable`. It will be removed in 3.1.0. Use `sw-sales-channel-defaults-select` instead.

# 3.0.0
- LAN-131 - Compatibility for Shopware 6.5.0.0

# 2.5.0
- LAN-133 - All supported languages got updated by community translations (Shopware version 6.4.19.0)
- LAN-133 - Deprecated `src/Core/Content/PackLanguageRepositoryDecorator.php` to be removed with 3.0.0

# 2.4.0
- LAN-119 - Add English (US) language support & Corrected Romanian locale from "ro-MD" to "ro-RO"

# 2.3.0
- LAN-90 - Updated extension description
- LAN-105 - All supported languages got updated by community translations (Shopware version 6.4.14.0)
- LAN-112 - Updated Country flags for storefront language selection

# 2.2.0
- LAN-91 - Fixed compatibility for Shopware 6.4.6.0
- LAN-91 - Bumped minimum Shopware version to ~6.4.6

# 2.1.2
- NTR - Compatibility for Shopware 6.4.5.0

# 2.1.1
- LAN-62 - Fixed language ACL issues
- LAN-82 - Fixed sales channel domain modal after adding a new language, when sales channel has not been saved before
- LAN-83 - Fixed a problem where the administration language would unnecessarily switch

# 2.1.0
- LAN-74 - Update translations for Shopware 6.4.1.0
- LAN-74 - Added Korean language support
- LAN-74 - Added Greek language support
- LAN-74 - Added Ukrainian language support
- LAN-74 - Added Turkish language support
- LAN-74 - Added Slovenian language support
- LAN-74 - Added Slovak language support
- LAN-74 - Added Serbian(Latin) language support
- LAN-74 - Added Hindi language support
- LAN-74 - Added Croatian language support
- LAN-74 - Added Bulgarian language support

# 2.0.1
- LAN-67 - Plugin is valid for the `dal:validate` console command

# 2.0.0
- LAN-53 - Compatibility for Shopware 6.4

# 1.2.1
- LAN-64 - Re-associate existing language import to translation instead of location

# 1.2.0
- B2B-459 - Added SwagEnterpriseSearch, PluginPublisher and B2bSuite
- LAN-56 - Exclude deactivated languages in sales-channel:create:storefront command

# 1.1.0
- LAN-56 - The sales-channel:create command now uses only the activated languages and the default ones
- LAN-57 - Adjusted language criteria, to display all languages, which are not linked to this language pack

# 1.0.0
- LAN-20 - Initial Language Pack release for Shopware 6
