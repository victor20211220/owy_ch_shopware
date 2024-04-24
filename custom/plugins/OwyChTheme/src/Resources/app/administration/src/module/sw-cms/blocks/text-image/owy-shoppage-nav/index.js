import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-shoppage-nav',
    label: 'Shop Page Sidebar Navigation',
    category: 'text-image',
    component: 'sw-cms-block-owy-shoppage-nav',
    previewComponent: 'sw-cms-block-preview-owy-shoppage-nav',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-shoppage-nav'
    }
});
