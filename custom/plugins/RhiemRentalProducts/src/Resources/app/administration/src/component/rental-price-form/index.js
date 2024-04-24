import template from './rental-price-form.html.twig'
import './rental-price-form.scss'

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const {Component} = Shopware;
const {mapPropertyErrors, mapState, mapGetters} = Shopware.Component.getComponentHelper();

Component.register('rental-price-form', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },
    data() {
        return {
            displayMaintainCurrencies: false
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

        ...mapPropertyErrors('product', ['taxId', 'price', 'purchasePrice']),

        ...mapState('rhiemRentalProduct', [
            'rentalProduct',
        ]),

        ...mapGetters('rhiemRentalProduct', [
            'isLoadingRental',
            'priceTaxRate',
            'parentRentalProduct'
        ]),

        restoreInheritancePriceSwitch() {
            return this.$refs.rentalPriceInheritanceWrapper.$children[0].$children[0];
        },

        removeInheritancePriceSwitch() {
            return this.$refs.rentalPriceInheritanceWrapper.$children[0].$children[0];
        }

    },

    methods: {
        customRemoveTaxInheritance(newValue) {
            this.removeInheritancePriceSwitch.$el.click();

            return newValue;
        },

        customRemovePriceInheritance(refPrice) {
            return [{
                currencyId: refPrice[0].currencyId,
                gross: refPrice[0].gross,
                net: refPrice[0].net,
                linked: refPrice[0].linked
            }];
        },

        customRestorePriceInheritance() {
            this.$refs.rentalPriceInheritanceWrapper.$emit('input', null);
            this.$refs.rentalPriceInheritanceWrapper.$emit(`inheritance-restore`);
        },

        customRestoreInheritance() {
            this.restoreInheritancePriceSwitch.$el.click();

            this.$emit('input', null)
        },
        onMaintainCurrenciesClose(prices) {
            this.rentalProduct.price = prices;

            this.displayMaintainCurrencies = false;
        }
    }
});
