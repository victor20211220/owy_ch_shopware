import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'acris-store-headline-google-map',
    label: 'acris-stores.cms.blocks.acris-store-headline-google-map.label',
    category: 'cms_stores',
    component: 'sw-cms-block-acris-store-headline-google-map',
    previewComponent: 'sw-cms-preview-acris-store-headline-google-map',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        column1: {
            type: 'text',
        },
        column2: {
            type: 'acris-store-google-map',
            default: {
                config: {
                    corner: {
                        source: 'static',
                        value: 'square'
                    },
                    verticalAlign: {
                        source: 'static',
                        value: 'center'
                    },
                    displayMode: {
                        source: 'static',
                        value: 'stretch'
                    }
                }
            }
        }
    }
});
