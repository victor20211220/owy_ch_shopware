const { Component } = Shopware;

Component.extend('netzp-searchadvanced6-synonyms-create', 'netzp-searchadvanced6-synonyms-detail', {
    methods: {
        getSynonym() {
            this.synonym = this.repository.create(Shopware.Context.api);
        },

        onClickSave() {
            this.synonym.synonym = this.synonym.synonym.toLowerCase();
            this.isLoading = true;

            this.repository
                .save(this.synonym, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.$router.push({ name: 'netzp.searchadvanced6.synonyms.detail', params: { id: this.synonym.id } });
                }).catch((exception) => {
                    this.isLoading = false;
                    if (exception.response.data && exception.response.data.errors) {
                        exception.response.data.errors.forEach((error) => {
                            this.createNotificationWarning({
                                title: this.$t('netzp-searchadvanced6-synonyms.detail.titleError'),
                                message: error.detail,
                                duration: 10000
                            });
                        });
                    }
                });
        }
    }
});
