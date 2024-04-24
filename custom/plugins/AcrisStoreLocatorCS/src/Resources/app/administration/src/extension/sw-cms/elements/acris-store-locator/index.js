import './component';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'acris-store-locator',
    label: 'acris-stores.cms.elements.acris-store-locator.label',
    component: 'sw-cms-el-acris-store-locator',
    previewComponent: 'sw-cms-el-preview-acris-store-locator'
});
