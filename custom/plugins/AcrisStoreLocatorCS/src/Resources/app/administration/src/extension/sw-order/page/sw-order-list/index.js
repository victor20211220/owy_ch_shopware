const { Component } = Shopware;

Component.override('sw-order-list', {

    computed:{
        orderColumns() {
            let columns = this.$super('orderColumns');
            const deliveryColumn = {
                property: 'deliveries[0].extensions.acrisOrderDeliveryStore.name',
                label: 'acris-stores.list.orderDeliveryStoreLabel',
                allowResize: true,
            }
            columns.push(deliveryColumn);
            return columns;

        }
    },

});
