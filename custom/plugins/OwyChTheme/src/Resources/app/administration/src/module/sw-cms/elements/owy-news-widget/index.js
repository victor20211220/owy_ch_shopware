import './component';import './config';Shopware.Service('cmsService').registerCmsElement({    name: 'owy-news-widget',    label: 'News Listing Widget',    component: 'sw-cms-el-owy-news-widget',    configComponent: 'sw-cms-el-config-owy-news-widget',    defaultConfig: {        owydate: {            required: true,            source: 'static',            value: ''        },        title: {            required: true,            source: 'static',            value: ''        },        shortdesc : {            required: true,            source: 'static',            value: ''        },        link: {            required: true,            source: 'static',            value: ''        }    }});