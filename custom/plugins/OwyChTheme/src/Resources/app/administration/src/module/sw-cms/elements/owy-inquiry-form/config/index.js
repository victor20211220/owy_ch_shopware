import template from './sw-cms-el-config-owy-inquiry-form.html.twig';

const { Component, Mixin } = Shopware;


Component.register('sw-cms-el-config-owy-inquiry-form', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],


    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-inquiry-form');
        },
    }
});
