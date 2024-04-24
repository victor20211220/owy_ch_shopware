import template from './acris-stores-create.html.twig';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.extend('acris-stores-create', 'acris-stores-detail', {
    template,

    beforeRouteEnter(to, from, next) {
        if (to.name.includes('acris.stores.create') && !to.params.id) {
            to.params.id = utils.createId();
            to.params.newItem = true;
        }

        next();
    },

    methods: {
        getStore() {
            this.item = this.repository.create(Shopware.Context.api);
            this.item.priority = 10;
        },

        createdComponent() {
            if (!Shopware.State.getters['context/isSystemDefaultLanguage']) {
                Shopware.State.commit('context/resetLanguageToDefault');
            }

            this.$super('createdComponent');
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({ name: 'acris.stores.detail', params: { id: this.item.id } });
        },

        onClickSave() {
            this.isLoading = true;
            const titleSaveError = this.$tc('acris-stores.detail.titleSaveError');
            const messageSaveError = this.$tc(
                'acris-stores.detail.messageSaveError', 0, {name: this.item.name}
            );
            const titleSaveSuccess = this.$tc('acris-stores.detail.titleSaveSuccess');
            const messageSaveSuccess = this.$tc(
                'acris-stores.detail.messageSaveSuccess', 0, {name: this.item.name}
            );

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });
                    this.$router.push({ name: 'acris.stores.detail', params: { id: this.item.id } });
                }).catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: titleSaveError,
                        message: messageSaveError
                    });
                });
        }
    }
});
