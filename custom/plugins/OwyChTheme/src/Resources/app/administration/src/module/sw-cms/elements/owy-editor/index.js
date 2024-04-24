import './component';
import './config';


Shopware.Service('cmsService').registerCmsElement({
    name: 'owy-editor',
    label: 'Owy Text Editor',
    component: 'sw-cms-el-owy-editor',
    configComponent: 'sw-cms-el-config-owy-editor',

    defaultConfig: {
        description : {
            required: true,
            source: 'static',
            value: ''
        },

    }
});
