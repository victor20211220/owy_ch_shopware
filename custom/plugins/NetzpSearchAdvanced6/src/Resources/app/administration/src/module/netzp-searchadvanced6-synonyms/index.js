import './page/netzp-searchadvanced6-synonyms-list';
import './page/netzp-searchadvanced6-synonyms-create';
import './page/netzp-searchadvanced6-synonyms-detail';
import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Shopware.Module.register('netzp-searchadvanced6-synonyms', {
    type: 'plugin',
    name: 'Search synonyms',
    title: 'netzp-searchadvanced6-synonyms.menuLabel',
    description: 'netzp-searchadvanced6-synonyms.main.menuDescription',
    color: '#ff3d58',
    icon: 'regular-eye',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'netzp-searchadvanced6-synonyms-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
        detail: {
            component: 'netzp-searchadvanced6-synonyms-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'netzp.searchadvanced6.synonyms.list'
            }
        },
        create: {
            component: 'netzp-searchadvanced6-synonyms-create',
            path: 'create',
            meta: {
                parentPath: 'netzp.searchadvanced6.synonyms.list'
            }
        }
    },

    settingsItem: {
        name: 'netzp-searchadvanced6-synonyms',
        to: 'netzp.searchadvanced6.synonyms.list',
        label: 'netzp-searchadvanced6-synonyms.menuLabel',
        group: 'plugins',
        icon: 'regular-search'
    }
});
