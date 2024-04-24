import template from './sw-cms-el-acris-store-group.html.twig';
import './sw-cms-el-acris-store-group.scss';

const { Component, Mixin, Filter } = Shopware;

Component.register('sw-cms-el-acris-store-group', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {

        pageType() {
            return this.cmsPageState?.currentPage?.type ?? '';
        },

        styles() {
            return {
                'min-height': this.element.config.displayMode.value === 'cover' &&
                              this.element.config.minHeight.value !== 0 ? this.element.config.minHeight.value : '320px',
                'align-self': !this.element.config.verticalAlign.value ? null : this.element.config.verticalAlign.value
            };
        },

        mediaUrl() {
            return this.assetFilter('acrisstorelocator/static/img/cms/store_group.PNG');
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
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('acris-store-group');
            this.initElementData('acris-store-group');
        }
    }
});
