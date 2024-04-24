import './component';
import './config';


Shopware.Service('cmsService').registerCmsElement({
    name: 'owy-broucher-widget',
    label: 'Broucher Download Widget',
    component: 'sw-cms-el-owy-broucher-widget',
    configComponent: 'sw-cms-el-config-owy-broucher-widget',

    defaultConfig: {
        broucher1image: {
            required: true,
            source: 'static',
            value: ''
        },
        broucher1title1: {
            required: true,
            source: 'static',
            value: ''
        },
        broucher1title2: {
            source: 'static',
            value: ''
        },
        broucher1pdf: {
            required: true,
            source: 'static',
            value: ''
        },

        broucher2image: {
            source: 'static',
            value: ''
        },
        broucher2title1: {

            source: 'static',
            value: ''
        },
        broucher2title2: {
            source: 'static',
            value: ''
        },
        broucher2pdf: {
            source: 'static',
            value: ''
        },
        broucher3image: {
            source: 'static',
            value: ''
        },
        broucher3title1: {
            source: 'static',
            value: ''
        },
        broucher3title2: {
            source: 'static',
            value: ''
        },
        broucher3pdf: {
            source: 'static',
            value: ''
        },
        broucher4image: {
            source: 'static',
            value: ''
        },
        broucher4title1: {
            source: 'static',
            value: ''
        },
        broucher4title2: {
            source: 'static',
            value: ''
        },
        broucher4pdf: {
            source: 'static',
            value: ''
        },


    }
});
