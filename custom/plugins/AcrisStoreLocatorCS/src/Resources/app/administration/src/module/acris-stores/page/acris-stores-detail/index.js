import template from './acris-stores-detail.html.twig';
import './acris-stores-detail.scss';

const { Component, Context, Mixin, Utils } = Shopware;
const { Criteria, ChangesetGenerator } = Shopware.Data;

const type = Shopware.Utils.types;
const { cloneDeep, merge } = Utils.object;
const { isEmpty } = Utils.types;

Component.register('acris-stores-detail', {
    template,

    inject: ['repositoryFactory', 'context', 'AcrisGetCoordsApiService'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    computed: {
        countryCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('states');

            return criteria;
        },

        storeMediaRepository() {
            return this.repositoryFactory.create(this.item.media.entity);
        },

        mediaFormVisible() {
            return !this.isLoading;
        },

        showState() {
            return !(!this.countryStates || this.countryStates.length === 0);
        },

        mediaDefaultFolderCriteria() {
            const criteria = new Criteria(1, 1);

            criteria.addAssociation('folder');
            criteria.addFilter(Criteria.equals('entity', 'acris_store_locator'));

            return criteria;
        },

        mediaDefaultFolderRepository() {
            return this.repositoryFactory.create('media_default_folder');
        },

        stateCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equalsAny('id', this.countryStates.getIds()));

            return criteria;
        },

        cmsPage() {
            return Shopware.State.get('cmsPageState').currentPage;
        },

        storeCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('country.states');
            criteria.addAssociation('storeGroup');
            criteria.addAssociation('cover.media');
            criteria.addAssociation('media');
            criteria.addAssociation('salesChannels');
            criteria.addAssociation('rules');

            return criteria;
        },

        cmsPageRepository() {
            return this.repositoryFactory.create('cms_page');
        }
    },

    data() {
        return {
            item: null,
            isLoading: false,
            processSuccess: false,
            repository: null,
            isSaveSuccessful: false,
            showLayoutModal: false,
            emptyCmsPage: null,
            countryStates: null,
            showMediaModal: false,
            mediaDefaultFolderId: null,
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

    methods: {
        createdComponent() {
            this.getMediaDefaultFolderId().then((mediaDefaultFolderId) => {
                this.mediaDefaultFolderId = mediaDefaultFolderId;
            });

            this.repository = this.repositoryFactory.create('acris_store_locator');
            this.getStore();
            this.umlautMap = {
                '\u00dc': 'Ue',
                '\u00c4': 'Ae',
                '\u00d6': 'Oe',
                '\u00fc': 'ue',
                '\u00e4': 'ae',
                '\u00f6': 'oe',
                '\u00df': 'ss',
            }
        },

        getMediaDefaultFolderId() {
            return this.mediaDefaultFolderRepository.search(this.mediaDefaultFolderCriteria, Context.api)
                .then((mediaDefaultFolder) => {
                    const defaultFolder = mediaDefaultFolder.first();

                    if (defaultFolder.folder?.id) {
                        return defaultFolder.folder.id;
                    }

                    return null;
                });
        },

        onOpenMediaModal() {
            this.showMediaModal = true;
        },

        onCloseMediaModal() {
            this.showMediaModal = false;
        },

        getStore() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api, this.storeCriteria)
                .then((entity) => {
                    this.item = entity;
                    if (this.item.country && this.item.country.states && this.item.country.states.length > 0){
                        this.countryStates = this.item.country.states;
                    }

                    this.getAssignedCmsPage();
                });
        },

        getAssignedCmsPage() {
            if (this.item.cmsPageId === null) {
                return Promise.resolve(null);
            }

            const criteria = new Criteria(1, 1);
            criteria.setIds([this.item.cmsPageId]);
            criteria.addAssociation('previewMedia');
            criteria.addAssociation('sections');
            criteria.getAssociation('sections').addSorting(Criteria.sort('position'));

            criteria.addAssociation('sections.blocks');
            criteria.getAssociation('sections.blocks')
                .addSorting(Criteria.sort('position', 'ASC'))
                .addAssociation('slots');

            return this.cmsPageRepository.search(criteria, Shopware.Context.api).then((response) => {
                const cmsPage = response.get(this.item.cmsPageId);

                if (this.item.slotConfig !== null) {
                    cmsPage.sections.forEach((section) => {
                        section.blocks.forEach((block) => {
                            block.slots.forEach((slot) => {
                                if (this.item.slotConfig[slot.id]) {
                                    if (slot.config === null) {
                                        slot.config = {};
                                    }
                                    merge(slot.config, cloneDeep(this.item.slotConfig[slot.id]));
                                }
                            });
                        });
                    });
                }
                Shopware.State.commit('cmsPageState/setCurrentPage', cmsPage);

                return cmsPage;
            });
        },

        onAddMedia(media) {
            if (isEmpty(media)) {
                return;
            }

            media.forEach((item) => {
                this.addMedia(item).catch(({ fileName }) => {
                    this.createNotificationError({
                        message: this.$tc('sw-product.mediaForm.errorMediaItemDuplicated', 0, { fileName }),
                    });
                });
            });
        },

        addMedia(media) {
            if (this.isExistingMedia(media)) {
                return Promise.reject(media);
            }

            const newMedia = this.storeMediaRepository.create(Context.api);
            newMedia.mediaId = media.id;
            newMedia.media = {
                url: media.url,
                id: media.id,
            };

            if (isEmpty(this.item.media)) {
                this.setMediaAsCover(newMedia);
            }

            this.item.media.add(newMedia);

            return Promise.resolve();
        },

        setMediaAsCover(media) {
            media.position = 0;
            this.item.coverId = media.id;
        },

        isExistingMedia(media) {
            return this.item.media.some(({ id, mediaId }) => {
                return id === media.id || mediaId === media.id;
            });
        },

        getCmsPageOverrides() {
            if (this.cmsPage === null) {
                return null;
            }

            this.deleteSpecifcKeys(this.cmsPage.sections);

            const changesetGenerator = new ChangesetGenerator();
            const { changes } = changesetGenerator.generate(this.cmsPage);

            const slotOverrides = {};
            if (changes === null) {
                return slotOverrides;
            }

            if (type.isArray(changes.sections)) {
                changes.sections.forEach((section) => {
                    if (type.isArray(section.blocks)) {
                        section.blocks.forEach((block) => {
                            if (type.isArray(block.slots)) {
                                block.slots.forEach((slot) => {
                                    slotOverrides[slot.id] = slot.config;
                                });
                            }
                        });
                    }
                });
            }

            return slotOverrides;
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

            this.isSaveSuccessful = false;
            this.isLoading = true;

            const pageOverrides = this.getCmsPageOverrides();

            if (type.isPlainObject(pageOverrides)) {
                this.item.slotConfig = cloneDeep(pageOverrides);
            }

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.getStore();
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

        onClickGetCoords() {
            const titleSaveError = this.$tc('acris-stores.detail.titleCalculateError');
            const messageSaveError = this.$tc(
                'acris-stores.detail.messageCalculateError', 0, {name: this.item.name}
            );

            const messageCalculateZeroResultError = this.$tc(
                'acris-stores.list.messageCalculateErrorNoResult'
            );
            const messageCalculateApiKeyError = this.$tc(
                'acris-stores.list.messageCalculateErrorApiKey'
            );

            // replaces umlaute if they are existing
            let street = this.replaceUmlaute(this.item.street);
            let city = this.replaceUmlaute(this.item.city);
            let country = this.replaceUmlaute(this.item.country.name);

            this.AcrisGetCoordsApiService.getCoords(
                street,
                this.item.zipcode,
                city,
                country
            ).then((response) => {
                if(response.success){
                    this.item.latitude = response[0].toString();
                    this.item.longitude = response[1].toString();
                }else{
                    if (response === 'The provided API key is invalid.') {
                        this.createNotificationError({
                            title: titleSaveError,
                            message: messageCalculateApiKeyError
                        });
                    } else {
                        if (response.includes("zeroResult")) {
                            this.createNotificationError({
                                title: titleSaveError,
                                message: messageCalculateZeroResultError+response.substring(10)
                            });
                        } else {
                            this.createNotificationError({
                                title: titleSaveError,
                                message: messageSaveError
                            });
                        }
                    }
                }
                this.isLoading = false;
            });

        },

        replaceUmlaute(str) {
            const umlautMap = this.umlautMap;
            return str
                .replace(/[\u00dc|\u00c4|\u00d6][a-z]/g, (a) => {
                    const big = umlautMap[a.slice(0, 1)];
                    return big.charAt(0) + big.charAt(1).toLowerCase() + a.slice(1);
                })
                .replace(new RegExp('['+Object.keys(umlautMap).join('|')+']',"g"),
                    (a) => umlautMap[a]
                );
        },

        saveFinish() {
            this.processSuccess = false;
        },

        onChangeLanguage() {
            this.getStore();
        },

        isSelected(itemId) {
            return typeof this.selection[itemId] !== 'undefined';
        },

        onCreateLayout() {
            if (!this.item) {
                return;
            }

            void this.$router.push({
                name: 'sw.cms.create'
            });
        },

        updateCmsPageId(cmsPageId){
            if (!this.item) {
                return;
            }

            this.item.cmsPageId = cmsPageId;
        },

        onCountryChange(id, item) {
            if (id && item) {
                this.item.countryId = id;
                this.countryStates = item.states;
                if (!item.states.getIds().includes(this.item.stateId)) {
                    this.item.stateId = null;
                }
            } else {
                this.item.countryId = null;
                this.countryStates = null;
                this.item.stateId = null;
            }
        },

        deleteSpecifcKeys(sections) {
            if (!sections) {
                return;
            }

            sections.forEach((section) => {
                if (!section.blocks) {
                    return;
                }

                section.blocks.forEach((block) => {
                    if (!block.slots) {
                        return;
                    }

                    block.slots.forEach((slot) => {
                        if (!slot.config) {
                            return;
                        }

                        Object.values(slot.config).forEach((configField) => {
                            if (configField.entity) {
                                delete configField.entity;
                            }
                            if (configField.required) {
                                delete configField.required;
                            }
                            if (configField.type) {
                                delete configField.type;
                            }
                        });
                    });
                });
            });
        }
    }
});
