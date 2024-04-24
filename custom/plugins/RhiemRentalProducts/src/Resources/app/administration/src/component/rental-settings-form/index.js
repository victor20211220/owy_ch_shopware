import template from './rental-settings-form.html.twig';
import './rental-settings-form.scss';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Component, Mixin } = Shopware;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('rental-settings-form', {
    template,

    props: {
        variants: {
            type: Boolean,
            required: false,
            default: true
        },
    },

    data: function() {
        return {
            inheritanceContext: { ...Shopware.Context.api, inheritance: true }
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        }
    },

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('notification')
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

        ...mapState('rhiemRentalProduct', [
            'rentalProduct'
        ]),

        modes() {
            return [
                {
                    value: 1,
                    label: this.$tc('rental-settings-form.dayRental')
                }
            ]
        }
    },

    methods: {
        setCloseoutAndStock(newValue) {
            this.$refs.parentRentalProductActiveInheritation.updateCurrentValue(newValue);
            this.product.isCloseout = this.rentalProduct.active ? false : this.product.isCloseout;

            if (this.rentalProduct.active) {
                this.rentalProduct.originalStock = this.product.stock;
            } else if (this.rentalProduct.originalStock > 0) {
                this.product.stock = this.rentalProduct.originalStock;
            }
        }
    }
});
