import template from './sw-cms-el-owy-switch-cmspage.html.twig';
import './sw-cms-el-owy-switch-cmspage.scss';

Shopware.Component.register('sw-cms-el-owy-switch-cmspage', {
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
            this.initElementConfig('owy-switch-cmspage');
        }

    }
});
