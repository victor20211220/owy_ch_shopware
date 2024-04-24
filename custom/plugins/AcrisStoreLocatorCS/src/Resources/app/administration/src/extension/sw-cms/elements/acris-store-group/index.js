import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'acris-store-group',
    label: 'acris-stores.cms.elements.acris-store-group.label',
    component: 'sw-cms-el-acris-store-group',
    configComponent: 'sw-cms-el-config-acris-store-group',
    previewComponent: 'sw-cms-el-preview-acris-store-group',
    disabledConfigInfoTextKey: 'acris-stores.cms.elements.acris-store-group.tooltipSettingDisabled',
    defaultConfig: {
        group: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'acris_store_group',
                criteria: criteria,
            },
        },
        displayType: {
            source: 'static',
            value: 'map_listing'
        },
    },
    defaultData: {
        group: {
            name: 'Lorem Ipsum dolor'
        },
        displayType: {
            name: 'Lorem Ipsum dolor'
        },
    },
    collect: function collect(elem) {
        const context = {
            ...Shopware.Context.api,
            inheritance: true,
        };

        const criteriaList = {};

        Object.keys(elem.config).forEach((configKey) => {
            if (elem.config[configKey].source === 'mapped') {
                return;
            }

            const config = elem.config[configKey];
            const configEntity = config.entity;
            const configValue = config.value;

            if (!configEntity || !configValue) {
                return;
            }


            const entityKey = configEntity.name;
            const entityData = {
                value: [configValue],
                key: configKey,
                searchCriteria: configEntity.criteria ? configEntity.criteria : new Criteria(),
                ...configEntity,
            };

            entityData.searchCriteria.setIds(entityData.value);
            entityData.context = context;

            criteriaList[`entity-${entityKey}`] = entityData;
        });

        return criteriaList;
    }
});
