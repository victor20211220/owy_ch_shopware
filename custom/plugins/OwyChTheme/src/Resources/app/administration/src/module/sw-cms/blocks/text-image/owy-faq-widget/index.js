import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-faq-widget',
    label: 'FAQ Widget',
    category: 'text-image',
    component: 'sw-cms-block-owy-faq-widget',
    previewComponent: 'sw-cms-block-preview-owy-faq-widget',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-faq-widget'
    }
});
