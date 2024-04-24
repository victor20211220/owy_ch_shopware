const { Component } = Shopware;

Component.override('sw-cms-list', {
    computed: {
        sortPageTypes() {
            let sortPageTypes = this.$super('sortPageTypes');
            const acrisStores = { value: 'cms_stores', name: this.$tc('acris-stores.cms.pageTypeCmsInCmsStoresPages') };
            sortPageTypes.push(acrisStores);

            return sortPageTypes;
        },

        pageTypes() {
            let pageTypes = this.$super('pageTypes');
            if (pageTypes && !pageTypes.cms_stores) {
                pageTypes.cms_stores = this.$tc('acris-stores.cms.pageTypeCmsInCmsStores');
            }

            return pageTypes;
        }
    }
});
