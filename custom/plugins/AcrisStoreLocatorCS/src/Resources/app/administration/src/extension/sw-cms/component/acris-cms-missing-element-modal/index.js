import template from './acris-cms-missing-element-modal.html.twig';

const { Component } = Shopware;

Component.extend('acris-cms-missing-element-modal', 'sw-cms-missing-element-modal', {
    template,

    computed: {
        title() {
            return this.$tc('acris-cms-missing-element-modal.cmsMissingElementModal.title', this.missingElements.length, {
                element: this.element,
            });
        }
    }
});
