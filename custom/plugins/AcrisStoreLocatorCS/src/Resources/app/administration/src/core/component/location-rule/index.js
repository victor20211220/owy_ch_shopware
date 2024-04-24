import template from './location-rule.html.twig';

const { Component, Context } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();
const { EntityCollection, Criteria } = Shopware.Data;

Component.extend('location-rule', 'sw-condition-base', {
    template,

    inject: ['repositoryFactory'],

    created() {
        this.createdComponent();
    },

    data() {
        return {
            locations: null,
            inputKey: 'locationIds',
        };
    },

    computed: {
        operators() {
            return this.conditionDataProviderService.getOperatorSet('multiStore');
        },

        locationRepository() {
            return this.repositoryFactory.create('acris_store_locator');
        },

        locationIds: {
            get() {
                this.ensureValueExist();
                return this.condition.value.locationIds || [];
            },
            set(locationIds) {
                this.ensureValueExist();
                this.condition.value = { ...this.condition.value, locationIds };
            }
        },

        ...mapPropertyErrors('condition', ['value.operator', 'value.locationIds']),

        currentError() {
            return this.conditionValueOperatorError || this.conditionValueLocationIdsError;
        }
    },

    methods: {
        createdComponent() {
            this.locations = new EntityCollection(
                this.locationRepository.route,
                this.locationRepository.entityName,
                Context.api
            );

            if (this.locationIds.length <= 0) {
                return Promise.resolve();
            }

            const criteria = new Criteria();
            criteria.setIds(this.locationIds);

            return this.locationRepository.search(criteria, Context.api).then((locations) => {
                this.locations = locations;
            });
        },

        setLocationIds(locations) {
            this.locationIds = locations.getIds();
            this.locations = locations;
        }
    }
});
