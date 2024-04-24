import './component';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsBlock({
    name: 'owy-category-tree-teaser',
    label: 'Sub category teaser',
    category: 'text-image',
    component: 'sw-cms-block-owy-category-tree-teaser',
    previewComponent: 'sw-cms-block-preview-owy-category-tree-teaser',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'owy-category-tree-teaser'
    }
});
