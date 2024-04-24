import template from './sw-order-list.html.twig';

const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-order-list', {
    template,

    data: function () {
        return {
            rentalFilter: false
        }
    },

    methods: {
        onlyRentalItems(event) {
            if (event) {
                if (!this.orderCriteria.hasAssociation("lineItems")) {
                    this.orderCriteria.addAssociation('lineItems');
                    this.orderCriteria.addFilter(Criteria.equals("lineItems.type", "rentalProduct"))
                }
            } else {
                let filters = this.orderCriteria.filters;
                for (let i = 0; i < filters.length; i++) {
                    if (filters[i].type === "equals" && filters[i].field === "lineItems.type" && filters[i].value === "rentalProduct") {
                        this.orderCriteria.filters.splice(i, 1);
                        break;
                    }
                }

                let associations = this.orderCriteria.associations;
                for (let i = 0; i < associations.length; i++) {
                    if (associations[i].association === "lineItems" ) {
                        this.orderCriteria.associations.splice(i, 1);
                        break;
                    }
                }
            }

            this.getList();
            this.rentalFilter = event;
        }
    }
});
