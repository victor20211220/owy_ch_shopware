import template from './sw-cms-el-config-owy-shoppage-nav.html.twig';
const { Component, Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;



Component.register('sw-cms-el-config-owy-shoppage-nav', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            categoryCollection: null,
        };
    },

    computed: {
        categoryRepository() {
            return this.repositoryFactory.create('category');
        },
        categories() {
            if (this.element.data && this.element.data.categories && this.element.data.categories.length > 0) {
                return this.element.data.categories;
            }
            return null;
        },
        categoryMediaFilter() {
            const criteria = new Criteria(1, 25);
            criteria.addAssociation('media');
            return criteria;
        },
        categoryMultiSelectContext() {
            const context = Object.assign({}, Shopware.Context.api);
            return context;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('owy-shoppage-nav');

            this.categoryCollection = new EntityCollection('/category', 'category', Shopware.Context.api);

            if (this.element.config.categories.value.length > 0) {
                const criteria = new Criteria(1, 100);
                criteria.addAssociation('media');
                criteria.setIds(this.element.config.categories.value);
                this.categoryRepository.search(criteria, Object.assign({}, Shopware.Context.api, {}))
                    .then(result => {
                        this.categoryCollection = result;
                    });
            }
        },

        onCategoryChange() {
            this.element.config.categories.value = this.categoryCollection.getIds();
            this.$set(this.element.data, 'categories', this.categoryCollection);
        },

    }
});
