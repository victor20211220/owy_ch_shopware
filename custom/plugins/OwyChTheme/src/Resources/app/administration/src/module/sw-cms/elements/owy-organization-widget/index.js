import './component';
import './config';


Shopware.Service('cmsService').registerCmsElement({
    name: 'owy-organization-widget',
    label: 'OWY Organization Widget',
    component: 'sw-cms-el-owy-organization-widget',
    configComponent: 'sw-cms-el-config-owy-organization-widget',

    defaultConfig: {
        heading: {
            required: true,
            source: 'static',
            value: null
        },
        org1image: {
            required: true,
            source: 'static',
            value: null
        },
        org1title1: {
            required: true,
            source: 'static',
            value: null
        },
        org1title2: {
            source: 'static',
            value: null
        },
        org1title3: {
            source: 'static',
            value: null
        },
        org2image: {
            source: 'static',
            value: null
        },
        org2title1: {
            source: 'static',
            value: null
        },
        org2title2: {
            source: 'static',
            value: null
        },
        org2title3: {
            source: 'static',
            value: null
        },
    }
});
