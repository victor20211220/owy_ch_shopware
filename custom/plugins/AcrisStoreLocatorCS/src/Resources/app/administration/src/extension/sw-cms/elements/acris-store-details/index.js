import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'acris-store-details',
    label: 'acris-stores.cms.elements.acris-store-details.label',
    component: 'sw-cms-el-acris-store-details',
    configComponent: 'sw-cms-el-config-acris-store-details',
    previewComponent: 'sw-cms-el-preview-acris-store-details',
    disabledConfigInfoTextKey: 'acris-stores.cms.elements.acris-store-details.tooltipSettingDisabled',
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
    }
});
