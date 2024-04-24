import template from './netzp-searchadvanced6-synonyms-list.html.twig';
import './netzp-searchadvanced6-synonyms-list.scss';

const { Component, Mixin} = Shopware;
const { Criteria } = Shopware.Data;

Component.register('netzp-searchadvanced6-synonyms-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('listing')
    ],

    data() {
        return {
            repository: null,
            synonym: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [
            {
                property: 'synonym',
                dataIndex: 'synonym',
                label: this.$t('netzp-searchadvanced6-synonyms.list.columns.synonym'),
                routerLink: 'netzp.searchadvanced6.synonyms.detail',
                allowResize: true,
                primary: true
            },
            {
                property: 'replace',
                dataIndex: 'replace',
                label: this.$t('netzp-searchadvanced6-synonyms.list.columns.replace'),
                allowResize: true,
            }
            ]
        }
    },

    methods: {
        getList() {
            this.repository = this.repositoryFactory.create('s_plugin_netzp_search_synonyms');
            const criteria = new Criteria(this.page, this.limit);
            criteria.addSorting(Criteria.sort('synonym', 'ASC'));

            this.repository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.synonym = result;
                });
        }
    },

    created() {
        this.getList();
    }
});
