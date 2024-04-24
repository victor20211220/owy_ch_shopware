import template from './sw-product-variants-overview.html.twig';


const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Shopware.Component.override('sw-product-variants-overview', {
    template,

    computed: {

        ...mapGetters('rhiemRentalProduct', [
            'isLoadingRental',
            'rentalProduct'
        ]),

        ...mapState('rhiemRentalProduct', [
            'isLoadingRental',
            'rentalProduct'
        ]),
    }
});
