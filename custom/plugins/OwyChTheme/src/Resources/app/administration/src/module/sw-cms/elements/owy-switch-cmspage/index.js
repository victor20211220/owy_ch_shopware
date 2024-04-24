import './component';
import './config';


Shopware.Service('cmsService').registerCmsElement({
    name: 'owy-switch-cmspage',
    label: 'Switch CMS Page Widget',
    component: 'sw-cms-el-owy-switch-cmspage',
    configComponent: 'sw-cms-el-config-owy-switch-cmspage',
    defaultConfig: {
        heading: {
            required: true,
            source: 'static',
            value: ''
        },
        title1: {
            required: true,
            source: 'static',
            value: ''
        },
        cmsPage1 : {
            required: true,
            source: 'static',
            value: ''
        },
        title2: {
            source: 'static',
            value: ''
        },
        cmsPage2 : {
            source: 'static',
            value: ''
        },
        title3: {
            source: 'static',
            value: ''
        },
        cmsPage3 : {
            source: 'static',
            value: ''
        },
        title4: {
            source: 'static',
            value: ''
        },
        cmsPage4 : {
            source: 'static',
            value: ''
        },
        title5: {
            source: 'static',
            value: ''
        },
        cmsPage5 : {
            source: 'static',
            value: ''
        },
        title6: {
            source: 'static',
            value: ''
        },
        cmsPage6 : {
            source: 'static',
            value: ''
        },
        title7: {
            source: 'static',
            value: ''
        },
        cmsPage7 : {
            source: 'static',
            value: ''
        },
        title8: {
            source: 'static',
            value: ''
        },
        cmsPage8: {
            source: 'static',
            value: ''
        }
    }
});
