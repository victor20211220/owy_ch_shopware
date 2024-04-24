import './page/acris-store-groups-list';
import './page/acris-store-groups-create';
import './page/acris-store-groups-detail';

import deDE from "./snippet/de-DE";
import enGB from "./snippet/en-GB";

const { Module } = Shopware;

Module.register('acris-store-groups', {
    type: 'plugin',
    name: 'AcrisStores',
    title: 'acris-store-groups.general.mainMenuItemGeneral',
    description: 'acris-store-groups.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#a6c836',
    icon: 'regular-users',
    favicon: 'icon-module-settings.png',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'acris-store-groups-list',
            path: 'index',
            meta: {
                parentPath: 'acris.store.locator.index'
            }
        },
        detail: {
            component: 'acris-store-groups-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'acris.store.groups.index'
            }
        },
        create: {
            component: 'acris-store-groups-create',
            path: 'create',
            meta: {
                parentPath: 'acris.store.groups.index'
            }
        }
    }
});
