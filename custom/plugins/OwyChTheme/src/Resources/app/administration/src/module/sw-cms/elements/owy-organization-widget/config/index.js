import template from './sw-cms-el-config-owy-organization-widget.html.twig';


const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-owy-organization-widget', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-organization-widget');
        }

    }
});
