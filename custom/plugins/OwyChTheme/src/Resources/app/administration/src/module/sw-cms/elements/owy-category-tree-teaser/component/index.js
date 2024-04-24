import template from './sw-cms-el-owy-category-tree-teaser.html.twig';


Shopware.Component.register('sw-cms-el-owy-category-tree-teaser', {
    template,

    mixins: [
        Shopware.Mixin.getByName('cms-element'),
        Shopware.Mixin.getByName('placeholder')
    ],
    data(){
        return {
            maxGridCount : 12
        }
    },
    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-category-tree-teaser');
        }

    }
});
