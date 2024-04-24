import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'owy-inquiry-form',
    category: 'form',
    label: 'sw-cms.blocks.form.owy-inquiry-form.title',
    component: "sw-cms-block-owy-inquiry-form",
    previewComponent: "sw-cms-preview-owy-inquiry-form",
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-inquiry-form'
    }
});