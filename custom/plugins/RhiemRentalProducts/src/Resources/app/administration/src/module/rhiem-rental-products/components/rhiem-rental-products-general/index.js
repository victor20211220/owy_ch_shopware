import template from './rhiem-rental-products-general.html.twig';

const { Component } = Shopware;

Component.register('rhiem-rental-products-general', {
    template,

    props: {
        actualConfigData: {
            type: Object,
            required: true,
        },
        isLoading: {
            type: Boolean,
            required: true,
        }
    }
});
