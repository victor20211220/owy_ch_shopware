import template from './sw-cms-el-config-owy-editor.html.twig';


const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-owy-editor', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-editor');
        }

    }
});
