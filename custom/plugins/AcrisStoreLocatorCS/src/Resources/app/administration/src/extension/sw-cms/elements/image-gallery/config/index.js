import template from './sw-cms-el-config-image-gallery.html.twig';

const { Component } = Shopware;

Component.override('sw-cms-el-config-image-gallery', {
    template,

    computed: {
        isStoreLocatorPage() {
            return (this.cmsPageState?.currentPage?.type ?? '') === 'cms_stores';
        }
    }
});
