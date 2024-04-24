import template from './sw-cms-el-config-owy-category-tree-teaser.html.twig';


const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-owy-category-tree-teaser', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-category-tree-teaser');
        }

    }
});
