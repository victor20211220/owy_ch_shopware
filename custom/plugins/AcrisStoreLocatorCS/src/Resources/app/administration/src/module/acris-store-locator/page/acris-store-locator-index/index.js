const { Mixin } = Shopware;
const { Component } = Shopware;

import template from './acris-store-locator-index.html.twig';

Component.register('acris-store-locator-index', {
    template,

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ]
});
