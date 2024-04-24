import template from './acris-store-groups-list.html.twig';
import './acris-store-groups-list.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('acris-store-groups-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            items: null,
            isLoading: false,
            showDeleteModal: false,
            repository: null,
            total: 0
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        entityRepository() {
            return this.repositoryFactory.create('acris_store_group');
        },

        columns() {
            return this.getColumns();
        }
    },

    methods: {
        getList() {
            this.isLoading = true;
            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            criteria.addAssociation('media');

            this.entityRepository.search(criteria, Shopware.Context.api).then((items) => {
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

        getColumns() {
            return [{
                property: 'internalId',
                inlineEdit: 'string',
                label: 'acris-store-groups.list.columnInternalId',
                routerLink: 'acris.store.groups.detail',
                allowResize: true,
                primary: true
            }, {
                property: 'internalName',
                inlineEdit: 'string',
                label: 'acris-store-groups.list.columnInternalName',
                routerLink: 'acris.store.groups.detail',
                allowResize: true,
                primary: true
            }, {
                property: 'name',
                inlineEdit: 'string',
                label: 'acris-store-groups.list.columnName',
                routerLink: 'acris.store.groups.detail',
                allowResize: true
            }, {
                property: 'position',
                inlineEdit: 'string',
                label: 'acris-store-groups.list.columnPosition',
                routerLink: 'acris.store.groups.detail',
                allowResize: true
            }, {
                property: 'priority',
                inlineEdit: 'number',
                label: 'acris-store-groups.list.columnPriority',
                routerLink: 'acris.store.groups.detail',
                allowResize: true
            }, {
                property: 'groupZoomFactor',
                inlineEdit: 'number',
                label: 'acris-store-groups.list.columnZoomFactor',
                routerLink: 'acris.store.groups.detail',
                allowResize: true
            }, {
                property: 'active',
                label: 'acris-store-groups.list.columnActive',
                inlineEdit: 'boolean',
                width: '80px',
                allowResize: true,
                align: 'center'
            }, {
                property: 'displayBelowMap',
                label: 'acris-store-groups.list.columnDisplayBelowMap',
                inlineEdit: 'boolean',
                width: '80px',
                allowResize: true,
                align: 'center'
            }];
        },

        onChangeLanguage(languageId) {
            this.getList(languageId);
        },

        onConfirmDelete(id) {
            this.showDeleteModal = false;
            return this.entityRepository.delete(id, Shopware.Context.api).then(() => {
                this.getList();
            });
        }
    }
});

