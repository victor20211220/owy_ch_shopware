const { Component } = Shopware;
const utils = Shopware.Utils;

import template from './acris-store-groups-create.html.twig';

Component.extend('acris-store-groups-create', 'acris-store-groups-detail', {
    template,

    beforeRouteEnter(to, from, next) {
        if (to.name.includes('acris.store.groups.create') && !to.params.id) {
            to.params.id = utils.createId();
            to.params.newItem = true;
        }

        next();
    },

    methods: {
        getEntity() {
            this.item = this.repository.create(Shopware.Context.api);
            this.item.priority = 10;
            this.item.active = false;
            this.item.iconWidth = 30;
            this.item.iconHeight = 30;
            this.item.iconAnchorLeft = 15;
            this.item.iconAnchorRight = 30;
            this.item.displayBelowMap = false;
            this.item.position = 'noDisplay';
            this.item.default = false;
            this.item.groupZoomFactor = 2;
            this.item.fieldList = ['name', 'department', 'phone', 'email', 'url', 'openingHours', 'city', 'zipcode', 'street', 'country'];
        },

        createdComponent() {
            if (!Shopware.State.getters['context/isSystemDefaultLanguage']) {
                Shopware.State.commit('context/resetLanguageToDefault');
            }

            this.$super('createdComponent');
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({ name: 'acris.store.groups.detail', params: { id: this.item.id } });
        },

        onClickSave() {
            this.isLoading = true;
            const titleSaveError = this.$tc('acris-store-groups.detail.titleSaveError');
            const messageSaveError = this.$tc('acris-store-groups.detail.messageSaveError');
            const titleSaveSuccess = this.$tc('acris-store-groups.detail.titleSaveSuccess');
            const messageSaveSuccess = this.$tc('acris-store-groups.detail.messageSaveSuccess');

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });
                    this.$router.push({ name: 'acris.store.groups.detail', params: { id: this.item.id } });
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
