import template from './sw-cms-el-config-owy-custom-form-widget.html.twig';const { Component, Mixin } = Shopware;Component.register('sw-cms-el-config-owy-custom-form-widget', {    template,    mixins: [        Mixin.getByName('cms-element')    ],    created() {        this.createdComponent();    },    methods: {        createdComponent() {            this.initElementConfig('owy-custom-form-widget');        }    }});