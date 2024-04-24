import './acl';
import './page/sendinblue-index';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('sendinblue-app', {
    type: 'plugin',
    name: 'Brevo',
    title: 'Brevo',
    description: 'sendinblue.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'regular-cog',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'sendinblue-index',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'system.sendinblue',
            },
        },
    },
    
    settingsItem: [
        {
            group: 'plugins',
            to: 'sendinblue.app.index',
            icon: 'regular-cog',
            backgroundEnabled: true,
            privilege: 'system.sendinblue'
        }
    ],

    extensionEntryRoute: {
        extensionName: 'NewsletterSendinblue',
        route: 'sendinblue.app.index'
    }
});
