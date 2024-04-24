const { Component } = Shopware;
const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './acris-store-groups-detail.html.twig';
import './acris-store-groups-detail.scss';

Component.register('acris-store-groups-detail', {
    template,

    inject: ['repositoryFactory', 'context'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    watch: {
        'item.media'() {
            if (this.item && this.item.media && this.item.media.id) {
                this.setMediaItem({targetId: this.item.media.id});
            }
        },
        'item.icon'() {
            if (this.item && this.item.icon && this.item.icon.id) {
                this.setMediaItemIcon({targetId: this.item.icon.id});
            }
        }
    },

    data() {
        return {
            item: null,
            isLoading: false,
            processSuccess: false,
            repository: null,
            mediaItem: null,
            mediaItemIcon: null,
            uploadTag: 'acris-store-group-upload-tag',
            uploadTagIcon: 'acris-store-group-upload-icon-tag',
            mediaModalIsOpen: false,
            mediaModalIsOpenIcon: false,
            isSaveSuccessful: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        positions() {
            return [
            // {
            //     label: this.$tc('acris-store-groups.detail.nextToSearchOption'),
            //     value: 'search'
            // },
            {
                label: this.$tc('acris-store-groups.detail.belowMapOption'),
                value: 'belowMap'
            },
            {
                label: this.$tc('acris-store-groups.detail.noDisplayOption'),
                value: 'noDisplay'
            }];
        },

        fieldList() {
            return [{
                label: this.$tc('acris-store-groups.detail.nameOption'),
                value: 'name'
            }, {
                label: this.$tc('acris-store-groups.detail.departmentOption'),
                value: 'department'
            }, {
                label: this.$tc('acris-store-groups.detail.phoneOption'),
                value: 'phone'
            }, {
                label: this.$tc('acris-store-groups.detail.emailOption'),
                value: 'email'
            }, {
                label: this.$tc('acris-store-groups.detail.urlOption'),
                value: 'url'
            }, {
                label: this.$tc('acris-store-groups.detail.openingHoursOption'),
                value: 'openingHours'
            }, {
                label: this.$tc('acris-store-groups.detail.cityOption'),
                value: 'city'
            }, {
                label: this.$tc('acris-store-groups.detail.zipcodeOption'),
                value: 'zipcode'
            }, {
                label: this.$tc('acris-store-groups.detail.streetOption'),
                value: 'street'
            }, {
                label: this.$tc('acris-store-groups.detail.countryOption'),
                value: 'country'
            }, {
                label: this.$tc('acris-store-groups.detail.stateOption'),
                value: 'state'
            }];
        },

        storeGroupCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('media');
            criteria.addAssociation('icon');

            return criteria;
        }
    },

    methods: {
        createdComponent(){
            this.repository = this.repositoryFactory.create('acris_store_group');
            this.getEntity();
        },

        getEntity() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api, this.storeGroupCriteria)
                .then((entity) => {
                    this.item = entity;
                });
        },

        setMediaItem({targetId}) {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((response) => {
                this.mediaItem = response;
            });
            this.item.mediaId = targetId;
        },

        onSelectionChanges(mediaEntity) {
            const media = mediaEntity[0];
            this.item.mediaId = media.id;
            this.mediaItem = media;
        },

        onDropMedia(mediaItem) {
            this.setMediaItem({targetId: mediaItem.id});
        },

        setMediaFromSidebar(mediaEntity) {
            this.mediaItem = mediaEntity;
            this.item.mediaId = mediaEntity.id;
        },

        onUnlinkAvatar() {
            this.mediaItem = null;
            this.item.mediaId = null;
        },

        onOpenMediaModal() {
            this.mediaModalIsOpen = true;
        },

        onCloseModal() {
            this.mediaModalIsOpen = false;
        },

        setMediaItemIcon({targetId}) {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((response) => {
                this.mediaItemIcon = response;
            });
            this.item.iconId = targetId;
        },

        onSelectionChangesIcon(mediaEntity) {
            const media = mediaEntity[0];
            this.item.iconId = media.id;
            this.mediaItemIcon = media;
        },

        onDropMediaIcon(mediaItem) {
            this.setMediaItemIcon({targetId: mediaItem.id});
        },

        setMediaFromSidebarIcon(mediaEntity) {
            this.mediaItemIcon = mediaEntity;
            this.item.iconId = mediaEntity.id;
        },

        onUnlinkAvatarIcon() {
            this.mediaItemIcon = null;
            this.item.iconId = null;
        },

        onOpenMediaModalIcon() {
            this.mediaModalIsOpenIcon = true;
        },

        onCloseModalIcon() {
            this.mediaModalIsOpenIcon = false;
        },

        onClickSave() {
            this.isLoading = true;
            const titleSaveError = this.$tc('acris-store-groups.detail.titleSaveError');
            const messageSaveError = this.$tc('acris-store-groups.detail.messageSaveError');
            const titleSaveSuccess = this.$tc('acris-store-groups.detail.titleSaveSuccess');
            const messageSaveSuccess = this.$tc('acris-store-groups.detail.messageSaveSuccess');

            this.isSaveSuccessful = false;
            this.isLoading = true;

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.getEntity();
                    this.isLoading = false;
                    this.processSuccess = true;
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });
                }).catch(() => {
                this.isLoading = false;
                this.createNotificationError({
                    title: titleSaveError,
                    message: messageSaveError
                });
            });
        },

        saveFinish() {
            this.processSuccess = false;
        },

        onChangeLanguage() {
            this.getEntity();
        },
    }
});
