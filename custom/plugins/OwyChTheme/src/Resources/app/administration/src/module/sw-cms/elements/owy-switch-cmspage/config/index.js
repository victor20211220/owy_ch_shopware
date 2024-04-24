import template from './sw-cms-el-config-owy-switch-cmspage.html.twig';


const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-cms-el-config-owy-switch-cmspage', {
    template,
    inject: ['cmsService'],

    mixins: [
        Mixin.getByName('cms-element')
    ],

   /* props: {
        pageType: {
            type: String,
            required: true,
        },

        value: {
            type: String,
            required: false,
            default: null,
        },
    },*/

    
    computed: {


       /* translations() {
            return this.getTranslations();
        },

        pageTypeCriteria() {
            const criteria = new Criteria(1, 25);

            criteria.addFilter(
                Criteria.equals('type', this.pageType),
            );

            return criteria;
        },*/
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-switch-cmspage');
        },
       /* getTranslations() {
            const translatableFields = ['label', 'placeholder', 'helpText'];

            const translations = {};
            translatableFields.forEach((field) => {
                if (this.$attrs[field] && this.$attrs[field] !== '') {
                    translations[field] = this.getInlineSnippet(this.$attrs[field]);
                }
            });

            return translations;
        },*/

    }
});
