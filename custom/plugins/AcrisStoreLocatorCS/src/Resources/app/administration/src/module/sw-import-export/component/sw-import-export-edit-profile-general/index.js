const profileTypes = {
    IMPORT: 'import',
    EXPORT: 'export',
    IMPORT_EXPORT: 'import-export',
};

/**
 * @private
 */
Shopware.Component.override('sw-import-export-edit-profile-general', {

    created() {
        this.supportedEntities = this.supportedEntities.push(this.storeLocatorEntity);
    },

    data() {
        return {
            storeLocatorEntity:
                {
                    value: 'acris_store_locator',
                    label: this.$tc('acris-stores.import-export-profile.storeLabel'),
                    type: profileTypes.IMPORT_EXPORT
                }
        };
    }
});
