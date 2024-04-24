import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-switch-cmspage',
    label: 'Switch CMS Page',
    category: 'text-image',
    component: 'sw-cms-block-owy-switch-cmspage',
    previewComponent: 'sw-cms-block-preview-owy-switch-cmspage',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-switch-cmspage'
    }
});
