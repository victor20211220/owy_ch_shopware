import template from './acris-store-image.html.twig';
import './acris-store-image.scss';

const { Component } = Shopware;

Component.extend('acris-store-image', 'sw-product-image', {
    template,

    props: {
        mediaId: {
            type: String,
            required: true,
        },

        isCover: {
            type: Boolean,
            required: false,
            default: false,
        },

        isPlaceholder: {
            type: Boolean,
            required: false,
            default: false,
        },

        showCoverLabel: {
            type: Boolean,
            required: false,
            default: true,
        },
    },

    computed: {
        storeImageClasses() {
            return {
                'is--placeholder': this.isPlaceholder,
                'is--cover': this.isCover && this.showCoverLabel,
            };
        },
    },
});
