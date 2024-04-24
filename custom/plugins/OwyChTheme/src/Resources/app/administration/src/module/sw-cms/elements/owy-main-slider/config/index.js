import template from './sw-cms-el-config-owy-main-slider.html.twig';


const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-owy-main-slider', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-main-slider');
        }

    }
});
