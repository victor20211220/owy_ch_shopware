import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-main-slider',
    label: 'Main Slider',
    category: 'text-image',
    component: 'sw-cms-block-owy-main-slider',
    previewComponent: 'sw-cms-block-preview-owy-main-slider',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-main-slider'
    }
});
