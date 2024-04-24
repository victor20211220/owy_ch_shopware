import template from './rental-bail-form.html.twig';
import './rental-bail-form.scss';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Component } = Shopware;
const { mapPropertyErrors, mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('rental-bail-form', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    data() {
        return {

        };
    },

    computed: {
        ...mapGetters('swProductDetail', [
            'isLoading',
            'defaultPrice',
            'defaultCurrency',
            'productTaxRate',
            'isChild'
        ]),

        ...mapState('swProductDetail', [
            'product',
            'productExtension',
            'parentProduct',
            'taxes',
            'currencies'
        ]),

        ...mapGetters('rhiemRentalProduct', [
            'bailTaxRate',
            'isLoadingRental',
            'parentRentalProduct'
        ]),

        ...mapState('rhiemRentalProduct', [
            'rentalProduct',
            'parentRentalProduct'
        ]),

        ...mapPropertyErrors('product', ['taxId', 'price', 'purchasePrice']),

        restoreInheritanceBailPriceSwitch() {
            return this.$refs.rentalBailPriceInheritanceWrapper.$children[0].$children[0];
        },

        removeInheritanceBailPriceSwitch() {
            return this.$refs.rentalBailPriceInheritanceWrapper.$children[0].$children[0];
        },

        hasParentRentalProduct() {
            if (Object.keys(this.parentRentalProduct).length !== 0) {
                return true;
            } else {
                return false;
            }
        }
    },

    methods: {

        customRemoveBailTaxInheritance(newValue) {
            this.removeInheritanceBailPriceSwitch.$el.click();

            return newValue;
        },

        customBailTaxRestoreInheritance() {
            this.restoreInheritanceBailPriceSwitch.$el.click();

            this.$emit('input', null)
        },

        customRemoveBailPriceInheritance(refPrice) {
            return [{
                currencyId: refPrice[0].currencyId,
                gross: refPrice[0].gross,
                net: refPrice[0].net,
                linked: refPrice[0].linked
            }];
        },

        customRestoreBailPriceInheritance() {
            this.$refs.rentalBailPriceInheritanceWrapper.$emit('input', null);
            this.$refs.rentalBailPriceInheritanceWrapper.$emit(`inheritance-restore`);
        },

    }
});