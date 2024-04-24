import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-awards-widget',
    label: 'Awards Widget',
    category: 'text-image',
    component: 'sw-cms-block-owy-awards-widget',
    previewComponent: 'sw-cms-block-preview-owy-awards-widget',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-awards-widget'
    }
});
