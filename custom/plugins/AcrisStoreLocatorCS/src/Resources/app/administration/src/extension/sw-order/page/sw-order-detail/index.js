const { Component } = Shopware;

Component.override('sw-order-detail', {
    computed: {
        orderCriteria() {
            const criteria = this.$super('orderCriteria');

            criteria
                .addAssociation('deliveries.acrisOrderDeliveryStore');

            return criteria;
        }
    }
});
