import deDE from "./snippet/de-DE";
import enGB from "./snippet/en-GB";

const { Module } = Shopware;

Module.register('acris-store-locator-rules', {

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    }
});
