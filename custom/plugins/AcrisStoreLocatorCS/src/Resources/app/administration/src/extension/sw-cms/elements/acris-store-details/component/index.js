import template from './sw-cms-el-acris-store-details.html.twig';
import './sw-cms-el-acris-store-details.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-cms-el-acris-store-details', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element'),
        Mixin.getByName('placeholder'),
    ],

    computed: {
        storeRepository() {
            return this.repositoryFactory.create('acris_store_locator');
        },

        selectedStoreCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('storeGroup');
            criteria.addAssociation('acrisOrderDeliveryStore');
            criteria.addAssociation('cmsPage');

            return criteria;
        },

        store() {
                if (!this.element?.data?.store) {
                    return {
                        name: 'Store name',
                        department: 'Department',
                        street: 'Street and number',
                        zipcode: 'Zipcode',
                        city: 'City',
                        phone: 'Phone',
                        url: 'Url',
                        opening_hours: 'Opening hours',
                    };
                }

                return this.element.data.store;
        },

        pageType() {
            return this.cmsPageState?.currentPage?.type ?? '';
        },

        isStorePageType() {
            return this.pageType === 'cms_stores';
        },

        currentDemoEntity() {
            if (this.cmsPageState.currentMappingEntity === 'acris_store_locator') {
                return this.cmsPageState.currentDemoEntity;
            }

            return null;
        },
    },

    watch: {
        pageType(newPageType) {
            this.$set(this.element, 'locked', newPageType === 'cms_stores');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('acris-store-details');
            this.initElementData('acris-store-details');
            this.$set(this.element, 'locked', this.isStorePageType);
        },
    },
});
