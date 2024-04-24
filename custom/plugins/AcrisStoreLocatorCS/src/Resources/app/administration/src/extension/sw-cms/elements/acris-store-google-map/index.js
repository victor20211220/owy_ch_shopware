import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'acris-store-google-map',
    label: 'acris-stores.cms.elements.acris-store-google-map.label',
    component: 'sw-cms-el-acris-store-google-map',
    configComponent: 'sw-cms-el-config-acris-store-google-map',
    previewComponent: 'sw-cms-el-preview-acris-store-google-map',
    disabledConfigInfoTextKey: 'acris-stores.cms.elements.acris-store-google-map.tooltipSettingDisabled',
    defaultConfig: {
        store: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'acris_store_locator',
                criteria: criteria,
            },
        }
    },
    defaultData: {
        store: {
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
