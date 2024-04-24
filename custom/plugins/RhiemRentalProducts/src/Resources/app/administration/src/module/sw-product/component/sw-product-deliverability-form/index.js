import template from './sw-product-deliverability-form.html.twig';

const {mapState, mapGetters} = Shopware.Component.getComponentHelper();

Shopware.Component.override('sw-product-deliverability-form', {
    template,

    computed: {

        ...mapGetters('rhiemRentalProduct', [
                'parentRentalProduct'
            ]),

        ...mapState('rhiemRentalProduct', [
            'rentalProduct'
        ]),

        checkForActive() {
            return this.rentalProduct.active || (this.parentRentalProduct.active && this.rentalProduct.active === null)
        }
    }
});
