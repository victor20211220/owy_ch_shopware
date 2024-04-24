import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-organization-widget',
    label: 'Organization Widget',
    category: 'text-image',
    component: 'sw-cms-block-owy-organization-widget',
    previewComponent: 'sw-cms-block-preview-owy-organization-widget',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-organization-widget'
    }
});
