import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'acris-store-group',
    label: 'acris-stores.cms.blocks.acris-store-group.label',
    category: 'cms_stores',
    component: 'sw-cms-block-acris-store-group',
    previewComponent: 'sw-cms-preview-acris-store-group',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        column1: {
            type: 'acris-store-group'
        }
    }
});
