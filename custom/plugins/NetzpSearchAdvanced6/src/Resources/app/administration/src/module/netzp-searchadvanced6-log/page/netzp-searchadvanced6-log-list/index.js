import template from './netzp-searchadvanced6-log-list.html.twig';
import './netzp-searchadvanced6-log-list.scss';
import Swal from "../../../../../node_modules/sweetalert2";

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('netzp-searchadvanced6-log-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('listing')
    ],

    data() {
        return {
            searchlog: null,
            searchgroups: null,
            groupQueries: false,
            onlyEmpty: false,

            filterLoading: false,
            salesChannelFilter: null,
            languageFilter: null,
            salesChannels: [],
            languages: [],
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        searchLogRepository()
        {
            return this.repositoryFactory.create('s_plugin_netzp_search_log');
        },

        salesChannelRepository()
        {
            return this.repositoryFactory.create('sales_channel');
        },

        languageRepository()
        {
            return this.repositoryFactory.create('language');
        },

        filterSalesChannelSelectCriteria()
        {
            const criteria = new Criteria(1, 100);
            criteria.addSorting(Criteria.sort('name', 'ASC'));

            return criteria;
        },

        filterLanguageSelectCriteria()
        {
            const criteria = new Criteria(1, 100);
            criteria.addSorting(Criteria.sort('name', 'ASC'));

            return criteria;
        },

        columns()
        {
            return [
            {
                property: 'query',
                dataIndex: 'query',
                label: this.$t('netzp-searchadvanced6-log.list.columns.query'),
                allowResize: true,
                primary: true
            },
            {
                property: 'hits',
                dataIndex: 'hits',
                label: this.$t('netzp-searchadvanced6-log.list.columns.hits')
            },
            {
                property: 'additionalHits',
                dataIndex: 'additionalHits',
                sortable: false,
                label: this.$t('netzp-searchadvanced6-log.list.columns.additionalHits')
            },
            {
                property: 'salesChannel',
                dataIndex: 'salesChannel.name',
                label: this.$t('netzp-searchadvanced6-log.list.columns.saleschannel')
            },
            {
                property: 'language',
                dataIndex: 'language.name',
                label: this.$t('netzp-searchadvanced6-log.list.columns.language')
            },
            {
                property: 'origin',
                dataIndex: 'origin',
                label: this.$t('netzp-searchadvanced6-log.list.columns.origin')
            },
            {
                property: 'createdAt',
                dataIndex: 'createdAt',
                label: this.$t('netzp-searchadvanced6-log.list.columns.date')
            }
            ]
        },

        columnsGrouped()
        {
            return [
                {
                    property: 'key',
                    label: this.$t('netzp-searchadvanced6-log.list.columns.query'),
                    sortable: true
                },
                {
                    property: 'count',
                    label: this.$t('netzp-searchadvanced6-log.list.columns.count'),
                    sortable: true
                }
            ]
        }
    },

    watch: {
        onlyEmpty(newVal)
        {
            this.getList();
        },

        groupQueries(newVal)
        {
            this.getList();
        },

        salesChannelFilter(newVal)
        {
            this.getList();
        },

        languageFilter(newVal)
        {
            this.getList();
        }
    },

    methods: {
        loadSalesChannelFilterValues()
        {
            this.filterLoading = true;
            return this.salesChannelRepository.search(this.filterSalesChannelSelectCriteria, Shopware.Context.api)
                .then((result) => {
                    this.salesChannels = result;
                    this.filterLoading = false;

                    return result;
                }).catch(() => {
                    this.filterLoading = false;
                });
        },

        loadLanguageFilterValues()
        {
            this.filterLoading = true;
            return this.languageRepository.search(this.filterLanguageSelectCriteria, Shopware.Context.api)
                .then((result) => {
                    this.languages = result;
                    this.filterLoading = false;

                    return result;
                }).catch(() => {
                    this.filterLoading = false;
                });
        },

        getList()
        {
            const criteria = new Criteria(this.page, this.limit);
            criteria.addAssociation('salesChannel');
            criteria.addAssociation('language');

            if(this.salesChannelFilter) {
                criteria.addFilter(Criteria.equals('salesChannel.id', this.salesChannelFilter));
            }
            if(this.languageFilter) {
                criteria.addFilter(Criteria.equals('language.id', this.languageFilter));
            }
            if(this.onlyEmpty) {
                criteria.addFilter(Criteria.equals('hits', 0));
            }

            if(this.groupQueries)
            {
                criteria.addAggregation(
                    Criteria.terms(
                        'query',
                        'query',
                        null,
                        Criteria.sort('_count', 'DESC')
                    )
                );

                this.searchLogRepository
                    .search(criteria, Shopware.Context.api)
                    .then(({aggregations}) => {
                        this.searchgroups = aggregations.query.buckets;
                    });
            }
            else
            {
                criteria.addSorting(Criteria.sort('createdAt', 'DESC'));
                this.searchLogRepository
                    .search(criteria, Shopware.Context.api)
                    .then((result) => {
                        this.searchlog = result;
                    });
            }
        },

        onRefresh()
        {
            this.getList();
        },

        resetFilter()
        {
            this.salesChannelFilter = null;
            this.languageFilter = null;
        },

        deleteAllLogEntries()
        {
            var logEntries = this.searchlog.map(function callback(item) {
                return item.id;
            });
            this.searchLogRepository.syncDeleted(logEntries, Shopware.Context.api).then(() => {
                this.getList();
            });
        },

        deleteLog()
        {
            Swal.fire({
                title: this.$tc('netzp-searchadvanced6-log.attention'),
                text: this.$tc('netzp-searchadvanced6-log.deleteLog'),
                icon: 'warning',
                showCancelButton: true,
                cancelButtonText: this.$tc('netzp-searchadvanced6-log.cancel'),
                confirmButtonText: this.$tc('netzp-searchadvanced6-log.ok')
            }).then((result) => {
                if (result.value) {
                    this.deleteAllLogEntries();
                }
            })
        },

        getExportUrl()
        {
            return location.origin + '/admin/netzp/searchadvanced/export';
        }
    },

    created()
    {
        this.getList();
        this.loadSalesChannelFilterValues();
        this.loadLanguageFilterValues();
    }
});
