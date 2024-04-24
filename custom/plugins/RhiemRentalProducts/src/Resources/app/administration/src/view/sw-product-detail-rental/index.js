import template from './sw-product-detail-rental.html.twig';
import './sw-product-detail-rental.scss'

const {Component} = Shopware;
const {mapState, mapGetters} = Component.getComponentHelper();
const {Context} = Shopware.Context.api;

Component.register('sw-product-detail-rental', {
    template,

    inject: [
        'repositoryFactory'
    ],

    computed: {
        ...mapGetters('swProductDetail', [
            'isLoading',
            'isChild'
        ]),

        ...mapState('swProductDetail', [
            'product',
            'parentProduct',
            'taxes',
            'currencies'
        ]),

        ...mapGetters('rhiemRentalProduct', [
            'isLoadingRental',
            'hasParentRentalProduct'
        ]),

        ...mapState('rhiemRentalProduct', [
            'rentalProduct'
        ]),

        dataRepository() {
            return this.repositoryFactory.create('rental_product');
        }
    }
});
