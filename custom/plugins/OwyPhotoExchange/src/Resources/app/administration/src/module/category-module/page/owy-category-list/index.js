const { Component } = Shopware;
const { Criteria } = Shopware.Data;
import template from './owy-category-list.html.twig';

Component.register('owy-category-list', {
    template,

    inject: [
        'repositoryFactory',
        'context'
    ],

    data() {
        return {
            repository: null,
            isLoading: false,
            Category: null,
            total: 0,
            sortBy: 'name',
            sortDirection: 'ASC',
            naturalSorting: true,
            showDeleteModal: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$t('owy-category-module.list.data.name'),
                routerLink: 'category.module.detail',
                inlineEdit: false,
                allowResize: true,
                primary: true
            },
                {
                property: 'isActive',
                dataIndex: 'isActive',
                label: this.$t('owy-category-module.list.data.active'),
                routerLink: 'category.module.detail',
                inlineEdit: false,
                allowResize: true
            }
            ];
        },
        faqRepository() {
            return this.repositoryFactory.create('photo_exchange_category');
        }
    },
    methods: {

        getList() {
            const criteria = new Criteria(this.page, this.limit);
            this.isLoading = true;

            this.naturalSorting = this.sortBy === 'name';
            criteria.setTerm(this.term);
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection, this.naturalSorting));
            this.categoryRepository.search(criteria, Shopware.Context.api).then((items) => {

                this.total = items.total;
                this.Category = items;
                this.isLoading = false;
                return items;
            }).catch(() => {
                this.isLoading = false;
            });
        }
    },
    created() {
        this.repository = this.repositoryFactory.create('photo_exchange_category');
        this.isLoading = true;
        this.repository
            .search(new Criteria(), Shopware.Context.api)
            .then((result) => {
                this.isLoading = false;
                this.Category = result;
            });
    }
});
