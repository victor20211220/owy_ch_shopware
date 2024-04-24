import template from './sw-customer-base-info.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-customer-base-info', {
    template,

    data() {
        return {
            acrisStore: null
        };
    },

    computed: {
        storeData() {
            if (this.acrisStore) {
                if (this.acrisStore.state) {
                    return this.acrisStore.translated.name+", "+this.acrisStore.street+", "+this.acrisStore.zipcode+", "+this.acrisStore.city+", "+this.acrisStore.country.name+", "+this.acrisStore.state.name;
                } else {
                    return this.acrisStore.translated.name+", "+this.acrisStore.street+", "+this.acrisStore.zipcode+", "+this.acrisStore.city+", "+this.acrisStore.country.name;
                }
            }
            return '';
        },

        storeRepository() {
            return this.repositoryFactory.create('acris_store_locator');
        },

        storeCriteria() {
            const criteria = new Criteria(1, 1);
            criteria.addAssociation('country');
            criteria.addAssociation('state');

            return criteria;
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');
            if (this.customer && this.customer.customFields && this.customer.customFields.acris_store_locator_assigned_store) {
                const storeId = this.customer.customFields.acris_store_locator_assigned_store;

                this.storeRepository.get(storeId, Shopware.Context.api, this.storeCriteria).then((store) => {
                    this.acrisStore = store;
                });
            }
        },
    },
});
