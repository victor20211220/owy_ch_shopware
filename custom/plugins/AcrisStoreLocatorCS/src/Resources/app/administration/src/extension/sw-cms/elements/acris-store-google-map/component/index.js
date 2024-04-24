import template from './sw-cms-el-acris-store-google-map.html.twig';
import './sw-cms-el-acris-store-google-map.scss';

const { Component, Mixin, Filter } = Shopware;

Component.register('sw-cms-el-acris-store-google-map', {
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

        styles() {
            return {
                'min-height': this.element.config.displayMode.value === 'cover' &&
                              this.element.config.minHeight.value !== 0 ? this.element.config.minHeight.value : '320px',
                'align-self': !this.element.config.verticalAlign.value ? null : this.element.config.verticalAlign.value
            };
        },

        mediaUrl() {
            return this.assetFilter('acrisstorelocator/static/img/cms/store_locator_map.PNG');
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
            this.initElementConfig('acris-store-google-map');
            this.initElementData('acris-store-google-map');
            this.$set(this.element, 'locked', this.isStorePageType);
        }
    }
});
