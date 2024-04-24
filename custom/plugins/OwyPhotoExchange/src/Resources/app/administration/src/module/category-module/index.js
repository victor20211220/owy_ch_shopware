const { Module } = Shopware;
import './page/owy-category-list';
import './page/owy-category-detail';
import './page/owy-category-create';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Module.register('category-module', {
    type: 'plugin',
    name: 'owy-category-module.name',
    title: 'owy-category-module.title',
    description: 'owy-category-module.description',
    color: '#62ff80',
    entity: 'photo_exchange_category',
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },
    routes: {
        list: {
            component: 'owy-category-list',
            path: 'list'
        },
        detail: {
            component: 'owy-category-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'category.module.list'
            }
        },
        create: {
            component: 'owy-category-create',
            path: 'create',
            meta: {
                parentPath: 'category.module.list'
            }
        }
    },

    navigation: [
        {
            label: 'owy-category-module.navigationLabel',
            color: '#57D9A3',
            path: 'category.module.list',
            icon: 'default-object-lab-flask',
            position: 201,
            parent: 'sw-catalogue'
        }

    ]
});