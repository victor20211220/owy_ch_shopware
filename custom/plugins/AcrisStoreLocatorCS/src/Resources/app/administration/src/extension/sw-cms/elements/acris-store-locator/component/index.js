import template from './sw-cms-el-acris-store-locator.html.twig';
import './sw-cms-el-acris-store-locator.scss';

const { Component, Mixin, Filter } = Shopware;

Component.register('sw-cms-el-acris-store-locator', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
        pageType() {
            return this.cmsPageState?.currentPage?.type ?? '';
        },

        isStorePageType() {
            return this.pageType === 'cms_stores';
        },

        mediaUrl() {
            return this.assetFilter('acrisstorelocator/static/img/cms/store_locator_page.PNG');
        },

        assetFilter() {
            return Filter.getByName('asset');
        }
    },

    watch: {
        cmsPageState: {
            deep: true,
            handler() {
                this.$forceUpdate();
            }
        },

        pageType(newPageType) {
            this.$set(this.element, 'locked', newPageType === 'cms_stores');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('acris-store-locator');
            this.initElementData('acris-store-locator');
            this.$set(this.element, 'locked', this.isStorePageType);
        }
    }
});
