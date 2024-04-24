import './component';
import './config';

Shopware.Service('cmsService').registerCmsElement({
    name: 'owy-inquiry-form',
    label: 'Inquiry Form',
    component: 'sw-cms-el-owy-inquiry-form',
    configComponent: 'sw-cms-el-config-owy-inquiry-form',
    defaultConfig: {
        title: {
            required: true,
            source: 'static',
            value: null
        },
        rubrikActive: {
            source: 'static',
            value: true
        },
        uberschriftActive: {
            source: 'static',
            value: true
        },
        eintragActive: {
            source: 'static',
            value: true
        },
        bildladenAtive1: {
            source: 'static',
            value: true
        },
        bildladenAtive2: {
            source: 'static',
            value: true
        },
        bildladenAtive3: {
            source: 'static',
            value: true
        },
        bildladenAtive4: {
            source: 'static',
            value: true
        }
    }
});
