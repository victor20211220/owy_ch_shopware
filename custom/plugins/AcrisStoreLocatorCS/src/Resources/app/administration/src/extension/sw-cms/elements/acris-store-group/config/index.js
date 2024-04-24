import template from './sw-cms-el-config-acris-store-group.html.twig';
import './sw-cms-el-config-acris-store-group.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-cms-el-config-acris-store-group', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    data() {
        return {
            isLoading: false
        };
    },

    computed: {
        storeRepository() {
            return this.repositoryFactory.create('acris_store_locator');
        },

        storeGroupRepository() {
            return this.repositoryFactory.create('acris_store_group');
        },

        groupSelectContext() {
            return {
                ...Shopware.Context.api,
                inheritance: true,
            };
        },

        groupCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('acrisStores');

            return criteria;
        },

        selectedGroupCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('acrisStores');

            return criteria;
        },

        isStorePage() {
            return false;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('acris-store-group');
            this.loadStandardGroup();
        },

        loadStandardGroup() {
            if (this.element && this.element.config && (!this.element.config.group || !this.element.config.group.value)) {
                this.isLoading = true;
                const criteria = new Criteria();
                criteria.addFilter(Criteria.equals('default', true));
                this.storeGroupRepository.search(criteria, Shopware.Context.api).then((res) => {
                    if (res.length > 0) {
                        this.element.config.group.value = res.first().id;
                        this.$set(this.element.data, 'groupId', res.first().id);
                        this.$set(this.element.data, 'group', res.first());
                    }
                    this.isLoading = false;
                }).catch(() => {
                    this.isLoading = false;
                });
            }
        },

        onGroupChange(groupId) {
            if (!groupId) {
                this.element.config.store.value = null;
                this.$set(this.element.data, 'groupId', null);
                this.$set(this.element.data, 'group', null);
            } else {
                this.storeRepository.get(groupId, this.groupSelectContext, this.selectedGroupCriteria)
                    .then((store) => {
                        this.element.config.group.value = groupId;
                        this.$set(this.element.data, 'groupId', groupId);
                        this.$set(this.element.data, 'group', store);
                    });
            }

            this.$emit('element-update', this.element);
        },
    }
});
