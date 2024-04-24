import template from './acris-stores-list.html.twig';
import './acris-stores-list.scss';

const {Component, StateDeprecated, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('acris-stores-list', {
    template,

    inject: ['repositoryFactory', 'context', 'AcrisCalcAndSaveCoordsApiService'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            items: null,
            isLoading: false,
            showProgressModal: false,
            showDeleteModal: false,
            repository: null,
            total: 0,
            errors: '',
            offsetProgress: 0,
            limitProgress: 25,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        storeRepository() {
            this.repository = this.repositoryFactory.create('acris_store_locator');
            return this.repositoryFactory.create('acris_store_locator');
        },

        storeColumns() {
            return this.getStoreColumns();
        },

        storeStore() {
            return StateDeprecated.getStore('acris_store_locator');
        },

        progressBarClasses() {
            return {
                'is--finished': (this.percentageProgress >= 100),
            };
        },

        percentageProgress() {
            if (this.items.total === 0) {
                return 0;
            }
            return this.offsetProgress / this.items.total * 100;
        },

    },

    methods: {
        getList() {
            this.isLoading = true;
            const criteria = new Criteria(this.page);
            criteria.setTerm(this.term);
            criteria.addAssociation('country');
            criteria.addAssociation('storeGroup');

            this.storeRepository.search(criteria, Shopware.Context.api).then((items) => {
                this.total = items.total;
                this.items = items;
                this.isLoading = false;

                return items;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete(id) {
            this.showDeleteModal = false;

            return this.storeStore.getById(id).delete(true).then(() => {
                this.getList();
            });
        },

        onCloseProgressModal() {
            this.showProgressModal = false;

        },

        getStoreColumns() {
            return [{
                property: 'internalId',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnInternalId',
                routerLink: 'acris.stores.detail',
                width: '280px',
                allowResize: true,
                primary: true
            }, {
                property: 'name',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnCompanyname',
                routerLink: 'acris.stores.detail',
                width: '280px',
                allowResize: true,
                primary: true
            }, {
                property: 'storeGroup.name',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnStoreGroupName',
                routerLink: 'acris.stores.detail',
                allowResize: true
            }, {
                property: 'priority',
                inlineEdit: 'number',
                label: 'acris-stores.list.columnPriority',
                routerLink: 'acris.stores.detail',
                allowResize: true
            }, {
                property: 'latitude',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnLatitude',
                routerLink: 'acris.stores.detail',
                allowResize: true
            }, {
                property: 'longitude',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnLongitude',
                routerLink: 'acris.stores.detail',
                allowResize: true
            }, {
                property: 'street',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnStreet',
                routerLink: 'acris.stores.detail',
                align: 'right',
                allowResize: true
            }, {
                property: 'zipcode',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnZip',
                routerLink: 'acris.stores.detail',
                align: 'left',
                allowResize: true
            }, {
                property: 'city',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnCity',
                routerLink: 'acris.stores.detail',
                allowResize: true
            }, {
                property: 'country.name',
                inlineEdit: 'string',
                label: 'acris-stores.list.columnCountry',
                routerLink: 'acris.stores.detail',
                align: 'right',
                allowResize: true
            }, {
                property: 'active',
                label: 'acris-stores.list.columnStoreLocator',
                inlineEdit: 'boolean',
                width: '80px',
                allowResize: true,
                align: 'center'
            }];
        },

        onChangeLanguage(languageId) {
            this.getList(languageId);
        },

        setProgressLimit() {
            if(this.items.total < this.limitProgress ){
                this.limitProgress = this.items.total/2;
            }
        },

        onClickGetCoords() {
            this.setProgressLimit();
            this.showProgressModal = true;
            this.AcrisCalcAndSaveCoordsApiService.calcAndSaveCoords(
                this.progressCallBack, this.offsetProgress, this.items.total, this.limitProgress, this.errors
            )
        },

        onClickPriority(itemId) {
            this.$router.push({ name: 'acris.stores.detail', params: { id: itemId } });
        },

        progressCallBack(response, isFinished) {
            const titleCalculateSuccess = this.$tc('acris-stores.list.titleCalculateSuccess');
            const titleCalculateInfo = this.$tc('acris-stores.list.titleCalculateInfo');
            let messageCalculateSuccess = this.$tc(
                'acris-stores.list.messageCalculateSuccess'
            );
            const titleCalculateError = this.$tc('acris-stores.list.titleCalculateError');
            const messageCalculateError = this.$tc(
                'acris-stores.list.messageCalculateError'
            );
            const messageCalculateErrorNoStores = this.$tc(
                'acris-stores.list.messageCalculateErrorNoStores'
            );

            const messageCalculateZeroResultError = this.$tc(
                'acris-stores.list.messageCalculateErrorNoResult'
            );
            const messageCalculateApiKeyError = this.$tc(
                'acris-stores.list.messageCalculateErrorApiKey'
            );
            const messageStoresError = this.$tc(
                'acris-stores.list.messageStoresWithError'
            );

            if (response.success) {
                this.errors = response.errors;
                this.offsetProgress = response.offset;
                if (!isFinished) {
                    this.showAddColorModal = true;
                } else {
                    this.createNotificationSuccess({
                        title: titleCalculateSuccess,
                        message: messageCalculateSuccess
                    });

                    if (response.errors) {
                        this.createNotificationInfo({
                            title: titleCalculateInfo,
                            message: messageStoresError+response.errors
                        });
                    }

                    this.showProgressModal = false;
                    this.offsetProgress = 0;
                    this.limitProgress = 25;
                    this.getList();
                }
            } else {
                if (response.error === 'no data') {
                    this.createNotificationError({
                        title: titleCalculateError,
                        message: messageCalculateErrorNoStores
                    });
                } else {
                    if (response.error === 'no permission') {
                        this.createNotificationError({
                            title: titleCalculateError,
                            message: messageCalculateError
                        });
                    } else {
                        if (response.error === 'The provided API key is invalid.') {
                            this.createNotificationError({
                                title: titleCalculateError,
                                message: messageCalculateApiKeyError
                            });
                        } else {
                            if (response.error.includes("zeroResult")) {
                                this.createNotificationError({
                                    title: titleCalculateError,
                                    message: messageCalculateZeroResultError+response.error.substring(10)
                                });
                            } else {
                                this.createNotificationError({
                                    title: titleCalculateError,
                                    message: response.error
                                });
                            }
                        }
                    }
                }
                this.showProgressModal = false;
            }
        },


    }
});

