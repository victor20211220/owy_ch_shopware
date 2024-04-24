import template from './sw-cms-el-owy-inquiry-form.html.twig';
import './sw-cms-el-owy-inquiry-form.scss';

Shopware.Component.register('sw-cms-el-owy-inquiry-form', {
    template,

    mixins: [
        Shopware.Mixin.getByName('cms-element'),
        Shopware.Mixin.getByName('placeholder')
    ],
    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-inquiry-form');
            this.initElementData('owy-inquiry-form');
        }
    }
});