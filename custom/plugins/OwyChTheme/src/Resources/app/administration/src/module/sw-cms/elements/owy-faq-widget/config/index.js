import template from './sw-cms-el-config-owy-faq-widget.html.twig';


const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-owy-faq-widget', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-faq-widget');
        }

    }
});
