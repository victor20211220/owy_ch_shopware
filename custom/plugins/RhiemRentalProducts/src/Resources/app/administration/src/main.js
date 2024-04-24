import './page/sw-product-detail';
import './view/sw-product-detail-rental';
import './module/sw-product/component/sw-product-deliverability-form/';
import './module/sw-product/page/sw-product-list/';
import './module/sw-product/component/sw-product-variants/sw-product-variants-overview/';
import './module/sw-order/component/sw-order-line-items-grid';
import './module/sw-order/page/sw-order-list/';
import './module/sw-product-variants/sw-product-modal-variant-generation/';


import './component/rental-variant-form/';
import './component/rental-settings-form/';
import './component/rental-deposit-form/';
import './component/rental-price-form/';
import './component/rental-restrictions-form/';
import './component/rental-calendar-form';
import './component/rental-calendar-day';
import './component/rental-calendar-block-modal';
import './component/rental-bail-form';
import './component/rhiem-product-detail-context-prices'

import './module/rhiem-rental-products';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);

import rentalProductState from './state/rental-state';

Shopware.State.registerModule('rhiemRentalProduct', rentalProductState);

const { Module } = Shopware;

Module.register('sw-new-tab-rental', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sw.product.detail.rental',
                path: '/sw/product/detail/:id/rental',
                component: 'sw-product-detail-rental',
                props: {
                    default: (route) => ({ productId: route.params.id })
                },
                meta: {
                    parentPath: 'sw.product.index'
                }
            });
            currentRoute.children.push({
                name: 'sw.product.detail.rentalPrices',
                path: '/rental-product/:id/prices',
                component: 'rhiem-product-detail-context-prices',
                props: {
                    default: (route) => ({ id: route.params.id })
                },
                meta: {
                    parentPath: 'sw.product.index'
                }
            });
        }
        next(currentRoute);
    }
});
