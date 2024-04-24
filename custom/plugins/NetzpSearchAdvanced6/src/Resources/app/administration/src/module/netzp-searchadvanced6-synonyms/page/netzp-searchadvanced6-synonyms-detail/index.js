import template from './netzp-searchadvanced6-synonyms-detail.html.twig';

const { Component, Mixin, StateDeprecated } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('netzp-searchadvanced6-synonyms-detail', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            synonym: null,
            isLoading: false,
            processSuccess: false,
            repository: null
        };
    },

    computed: {
        ...mapPropertyErrors('synonym', ['synonym', 'replace'])
    },

    created() {
        this.repository = this.repositoryFactory.create('s_plugin_netzp_search_synonyms');
        this.getSynonym();
    },

    methods: {
        getSynonym() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.synonym = entity;
                });
        },

        onClickSave() {
            this.synonym.synonym = this.synonym.synonym.toLowerCase();
            this.isLoading = true;

            this.repository
                .save(this.synonym, Shopware.Context.api)
                .then(() => {
                    this.getSynonym();
                    this.isLoading = false;
                    this.processSuccess = true;
                }).catch((exception) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$t('netzp-searchadvanced6-synonyms.detail.error.title'),
                        message: exception
                });
            });
        },

        saveFinish() {
            this.processSuccess = false;
        }
    }
});
