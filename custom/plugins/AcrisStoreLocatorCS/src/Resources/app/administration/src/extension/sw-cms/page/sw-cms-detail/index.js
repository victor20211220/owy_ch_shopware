import template from './sw-cms-detail.html.twig';
const { Component, Utils } = Shopware;

Component.override('sw-cms-detail', {
    template,

    data() {
        return {
            uniqueStoreSlots: [
                'acris-store-google-map',
                'acris-store-details'
            ]
        };
    },

    computed: {
        cmsPageTypes() {
            let cmsPageTypes = this.$super('cmsPageTypes');
            if (cmsPageTypes && !cmsPageTypes.cms_stores) {
                cmsPageTypes.cms_stores = this.$tc('acris-stores.cms.pageTypeCmsInCmsStores');
            }

            return cmsPageTypes;
        },

        cmsTypeMappingEntities() {
            let mappingEntities = this.$super('cmsTypeMappingEntities');

            if (mappingEntities && !mappingEntities.cms_stores) {
                mappingEntities.cms_stores = {
                    entity: 'acris_store_locator',
                    mode: 'single',
                };
            }

            return mappingEntities;
        },

        cmsPageTypeSettings() {
            const storeMappingEntity = {
                cms_stores: {
                    entity: 'acris_store_locator',
                    mode: 'single'
                }
            }

            if (this.page.type === 'cms_stores') {
                return storeMappingEntity[this.page.type];
            } else {
                return this.$super('cmsPageTypeSettings');
            }
        },
    },

    methods: {
        isStorePageElement(slot) {
            return ['acris-store-google-map', 'acris-store-details'].includes(slot.type);
        },

        slotValidation() {
            let valid = true;
            if (this.page.type === 'cms_stores') {
                const mappedUniqueStoreSlots = this.uniqueStoreSlots.map((slotName) => slotName.replace(/-./g, char => char.toUpperCase()[1]));

                const { requiredMissingSlotConfigs, uniqueSlotCount } = this.getStoreSlotValidations();
                const affectedErrorElements = [];
                const affectedWarningElements = [];

                if (this.page.type === 'cms_stores') {
                    mappedUniqueStoreSlots.forEach((index) => {
                        if (uniqueSlotCount?.[index] > 1) {
                            affectedErrorElements.push(this.$tc(`sw-cms.elements.${index}.label`));

                            valid = false;
                        } else if (!uniqueSlotCount?.[index]) {
                            affectedWarningElements.push(this.$tc(`sw-cms.elements.${index}.label`));
                        }
                    });

                    if (affectedErrorElements.length > 0) {
                        const uniqueSlotString = mappedUniqueStoreSlots
                            .map(slot => this.$tc(`sw-cms.elements.${slot}.label`))
                            .join(', ');
                        const message = this.$tc('sw-cms.detail.notification.messageRedundantElements', 0, {
                            names: uniqueSlotString,
                        });

                        this.addError({
                            property: 'slots',
                            code: 'uniqueSlotsOnlyOnce',
                            message,
                            payload: {
                                children: affectedErrorElements,
                            },
                        });
                    }

                    if (affectedWarningElements.length > 0) {
                        this.validationWarnings.push(...affectedWarningElements);
                    }
                }

                if (requiredMissingSlotConfigs.length > 0) {
                    this.addError({
                        property: 'slotConfig',
                        code: 'requiredConfigMissing',
                        message: this.$tc('sw-cms.detail.notification.messageMissingBlockFields'),
                        payload: {
                            children: requiredMissingSlotConfigs,
                        },
                    });

                    valid = false;
                }
            } else {
                valid = this.$super('slotValidation');
            }

            return valid;
        },

        getStoreSlotValidations() {
            const uniqueSlotCount = {};
            const requiredMissingSlotConfigs = [];

            this.page.sections.forEach((section) => {
                section.blocks.forEach((block) => {
                    block.slots.forEach((slot) => {
                        if (this.page.type === 'cms_stores' && this.isStorePageElement(slot)) {
                            const camelSlotType = Utils.string.camelCase(slot.type);
                            if (!uniqueSlotCount.hasOwnProperty(camelSlotType)) {
                                uniqueSlotCount[camelSlotType] = 1;
                            } else {
                                uniqueSlotCount[camelSlotType] += 1;
                            }

                            return;
                        }

                        requiredMissingSlotConfigs.push(...this.checkRequiredSlotConfigField(slot));
                    });
                });
            });

            return {
                requiredMissingSlotConfigs,
                uniqueSlotCount,
            };
        }
    }
});
