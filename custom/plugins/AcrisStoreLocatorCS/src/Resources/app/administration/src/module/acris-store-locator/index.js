import './page/acris-store-locator-index';
import './acris-settings-item.scss';

import deDE from "./snippet/de-DE";
import enGB from "./snippet/en-GB";

const { Module } = Shopware;

Module.register('acris-store-locator', {
    type: 'plugin',
    name: 'AcrisStoreLocator',
    title: 'acris-store-locator.general.mainMenuItemGeneral',
    description: 'acris-store-locator.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#a6c836',
    icon: 'regular-home',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'acris-store-locator-index',
            path: 'index',
            icon: 'regular-home',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },

    settingsItem: [
        {
            name:   'acris-store-locator-index',
            to:     'acris.store.locator.index',
            label:  'acris-store-locator.general.mainMenuItemGeneral',
            group:  'plugins',
            icon:   'regular-home'
        }
    ]
});
