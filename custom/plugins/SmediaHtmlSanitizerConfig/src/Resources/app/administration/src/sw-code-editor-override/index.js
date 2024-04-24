
Shopware.Component.override('sw-code-editor', {

    inject: ['systemConfigApiService'],

    computed: {
        enableHtmlSanitizer() {
            return false;
        }
    }

});