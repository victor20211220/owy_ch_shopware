import template from './sw-cms-el-config-image-slider.html.twig';
import './sw-cms-el-config-image-slider.scss';

const { Component } = Shopware;
Component.override('sw-cms-el-config-image-slider', {
    template
});
