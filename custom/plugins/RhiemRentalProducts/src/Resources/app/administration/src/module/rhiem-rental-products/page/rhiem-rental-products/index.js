import template from './rhiem-rental-products.html.twig';
import './rhiem-rental-products.scss';

const { Component } = Shopware;

Component.register('rhiem-rental-products', {
    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        'notification',
    ],

    data() {
        return {
            config: null,
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    methods: {
        onSave() {
            this.isLoading = true;

            this.$refs.configComponent.save().then((response) => {

            }).finally(() => {
                this.isLoading = false;
            });
        },
    },
});
