import template from './rental-restrictions-form.html.twig'
import './rental-restriction-form.scss'

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Shopware.Component.register('rental-restrictions-form', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

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

        ...mapState('rhiemRentalProduct', [
            'rentalProduct'
        ]),
    },

    methods: {
        handleFixedPeriod(fixedPeriod) {
            if(fixedPeriod) {
                this.rentalProduct.maxPeriod = this.rentalProduct.minPeriod;
            }
        },

        minPeriodChanged(minPeriod) {
            if(!this.rentalProduct.fixedPeriod) return;

            this.rentalProduct.maxPeriod = minPeriod;
        }
    }
});
