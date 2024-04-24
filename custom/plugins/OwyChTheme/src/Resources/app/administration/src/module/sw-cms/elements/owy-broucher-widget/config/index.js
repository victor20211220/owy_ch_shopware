import template from './sw-cms-el-config-owy-broucher-widget.html.twig';


const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-owy-broucher-widget', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-broucher-widget');
        }

    }
});
