import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'acris-store-locator-page',
    label: 'acris-stores.cms.blocks.acris-store-locator-page.label',
    category: 'cms_stores',
    component: 'sw-cms-block-acris-store-locator-page',
    previewComponent: 'sw-cms-preview-acris-store-locator-page',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        column1: {
            type: 'acris-store-locator'
        }
    }
});
