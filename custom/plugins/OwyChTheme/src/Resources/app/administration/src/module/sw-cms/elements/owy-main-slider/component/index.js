import template from './sw-cms-el-owy-main-slider.html.twig';
import './sw-cms-el-owy-main-slider.scss';

Shopware.Component.register('sw-cms-el-owy-main-slider', {
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
            this.initElementConfig('owy-main-slider');
        }

    }
});
