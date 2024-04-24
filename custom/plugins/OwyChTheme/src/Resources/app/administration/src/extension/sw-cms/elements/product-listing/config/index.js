import template from './index.html.twig';
const { Component } = Shopware;
Component.override('sw-cms-el-config-product-listing', {
    template
});
