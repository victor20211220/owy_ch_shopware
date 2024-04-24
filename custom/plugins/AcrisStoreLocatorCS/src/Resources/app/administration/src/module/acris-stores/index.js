import './page/acris-stores-list';
import './page/acris-stores-create';
import './page/acris-stores-detail';

import './component/acris-store-media-form';
import './component/acris-store-image';

import deDE from "./snippet/de-DE";
import enGB from "./snippet/en-GB";

const { Module } = Shopware;

Module.register('acris-stores', {
    type: 'plugin',
    name: 'AcrisStores',
    title: 'acris-stores.general.mainMenuItemGeneral',
    description: 'acris-stores.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#a6c836',
    icon: 'regular-home',
    favicon: 'icon-module-settings.png',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'acris-stores-list',
            path: 'index',
            meta: {
                parentPath: 'acris.store.locator.index'
            }
        },
        detail: {
            component: 'acris-stores-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'acris.stores.index'
            }
        },
        create: {
            component: 'acris-stores-create',
            path: 'create',
            meta: {
                parentPath: 'acris.stores.index'
            }
        }
    },

    navigation: [{
        id: 'acris-stores',
        label: 'acris-stores.general.mainMenuItemGeneral',
        path: 'acris.stores.index',
        position: 100,
        parent: 'sw-content'
    }]
});
