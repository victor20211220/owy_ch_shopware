import template from './sw-cms-el-owy-awards-widget.html.twig';
import './sw-cms-el-owy-awards-widget.scss';



Shopware.Component.register('sw-cms-el-owy-awards-widget', {
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
            this.initElementConfig('owy-awards-widget');
        }

    }
});
