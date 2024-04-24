import template from './sw-order-detail-base.html.twig';
import './sw-order-detail-base.scss';

const {Component} = Shopware;

Component.override('sw-order-detail-base', {
    template,

    computed: {
        orderCriteria() {
            const criteria = this.$super('orderCriteria');
            criteria.addAssociation('deliveries.acrisOrderDeliveryStore');
            return criteria;
        },

        currentDeliveryClass() {
            if (this.order.deliveries.length > 1) {
                return `has--multiple-deliveries`;
            }

            return `has--one-delivery`;
        }
    },

    methods: {
        getStoreId() {
            if (this.order.deliveries[0].extensions.acrisOrderDeliveryStore.storeId) {
                return this.order.deliveries[0].extensions.acrisOrderDeliveryStore.storeId
            }
            return null;
        },

        onDeliveryChange(id, entity) {
            if (entity) {
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.storeId = entity.id;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.countryId = entity.countryId;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.name = entity.translated.name;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.department = entity.translated.department;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.city = entity.city;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.zipcode = entity.zipcode;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.street = entity.street;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.phone = entity.phone;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.email = entity.email;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.url = entity.url;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.opening_hours = entity.opening_hours;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.longitude = entity.longitude;
                this.order.deliveries[0].extensions.acrisOrderDeliveryStore.latitude = entity.latitude;
            }
        }
    }
});
