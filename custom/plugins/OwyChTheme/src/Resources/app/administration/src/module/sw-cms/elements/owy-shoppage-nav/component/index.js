import template from './sw-cms-el-owy-shoppage-nav.html.twig';import './sw-cms-el-owy-shoppage-nav.scss';Shopware.Component.register('sw-cms-el-owy-shoppage-nav', {    template,    mixins: [        Shopware.Mixin.getByName('cms-element'),        Shopware.Mixin.getByName('placeholder')    ],    created() {        this.createdComponent();    },    methods: {        createdComponent() {            this.initElementConfig('owy-shoppage-nav');            this.initElementData('owy-shoppage-nav');        }    }});