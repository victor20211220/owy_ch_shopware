import './page/rhiem-rental-products';

import './components/rhiem-rental-products-block';
import './components/rhiem-rental-products-general';
import './components/rhiem-rental-products-settings-icon';

import deDeSnippets from './snippet/de-DE.json';
import enGBSnippets from './snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDeSnippets);
Shopware.Locale.extend('en-GB', enGBSnippets);

const { Module } = Shopware;

Module.register('rhiem-rental-products', {
    type: 'plugin',
    name: 'RhiemRentalProducts',
    title: 'rhiem-rental-products.config.title',
    description: 'rhiem-rental-products.config.description',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'default-action-settings',

    routes: {
        index: {
            component: 'rhiem-rental-products',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
    },

    settingsItem: {
        group: 'plugins',
        to: 'rhiem.rental.products.index',
        iconComponent: 'rhiem-rental-products-settings-icon',
        backgroundEnabled: true,
    },
});
