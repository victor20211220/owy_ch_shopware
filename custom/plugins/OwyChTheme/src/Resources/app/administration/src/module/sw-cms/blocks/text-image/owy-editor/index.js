import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-editor',
    label: 'Owy Editor',
    category: 'text-image',
    component: 'sw-cms-block-owy-editor',
    previewComponent: 'sw-cms-block-preview-owy-editor',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-editor'
    }
});
