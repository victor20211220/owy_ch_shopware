import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-broucher-widget',
    label: 'Broucher PDF Download',
    category: 'text-image',
    component: 'sw-cms-block-owy-broucher-widget',
    previewComponent: 'sw-cms-block-preview-owy-broucher-widget',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-broucher-widget'
    }
});
