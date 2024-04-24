import template from './sw-settings-index.html.twig';

/**
 * @package services-settings
 */
Shopware.Component.override('sw-settings-index', {
    template,

    computed: {
        canViewSecuritySettings() {
            const acl = Shopware.Service('acl');

            if (!acl) {
                return true;
            }

            return acl.can('admin');
        }
    }
});
