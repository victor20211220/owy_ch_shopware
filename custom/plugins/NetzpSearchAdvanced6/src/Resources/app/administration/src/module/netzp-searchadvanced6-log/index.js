import './page/netzp-searchadvanced6-log-list';
import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Shopware.Module.register('netzp-searchadvanced6-log', {
    type: 'plugin',
    name: 'Search log',
    title: 'netzp-searchadvanced6-log.menuLabel',
    description: 'netzp-searchadvanced6-log.main.menuDescription',
    color: '#ff3d58',
    icon: 'regular-eye',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'netzp-searchadvanced6-log-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },

    navigation: [{
        label: 'netzp-searchadvanced6-log.menuLabel',
        color: '#ff3d58',
        path: 'netzp.searchadvanced6.log.list',
        icon: 'regular-image',
        parent: 'sw-dashboard',
        position: 100
    }]
});
