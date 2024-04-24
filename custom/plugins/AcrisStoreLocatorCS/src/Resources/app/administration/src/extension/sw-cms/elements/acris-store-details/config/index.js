import template from './sw-cms-el-config-acris-store-details.html.twig';
import './sw-cms-el-config-acris-store-details.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-cms-el-config-acris-store-details', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    computed: {
        storeRepository() {
            return this.repositoryFactory.create('acris_store_locator');
        },

        storeSelectContext() {
            return {
                ...Shopware.Context.api,
            };
        },

        storeCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('storeGroup');
            criteria.addAssociation('acrisOrderDeliveryStore');
            criteria.addAssociation('cmsPage');

            return criteria;
        },

        selectedStoreCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('storeGroup');
            criteria.addAssociation('acrisOrderDeliveryStore');
            criteria.addAssociation('cmsPage');

            return criteria;
        },

        isStorePage() {
            return this.cmsPageState?.currentPage?.type === 'cms_stores';
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('acris-store-details');
            this.initElementData('acris-store-details');
        },

        onStoreChange(storeId) {
            if (!storeId) {
                this.element.config.store.value = null;
                this.$set(this.element.data, 'storeId', null);
                this.$set(this.element.data, 'store', null);
            } else {
                this.storeRepository.get(storeId, Shopware.Context.api, this.selectedStoreCriteria)
                    .then((store) => {
                        this.element.config.store.value = storeId;
                        this.$set(this.element.data, 'storeId', storeId);
                        this.$set(this.element.data, 'store', store);
                    });
            }

            this.$emit('element-update', this.element);
        },
    }
});
