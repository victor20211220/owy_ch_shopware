import template from './sw-cms-el-config-image-slider.html.twig';

const { Component } = Shopware;

Component.override('sw-cms-el-config-image-slider', {
    template,

    computed: {
        isStoreLocatorPage() {
            return (this.cmsPageState?.currentPage?.type ?? '') === 'cms_stores';
        }
    }
});
