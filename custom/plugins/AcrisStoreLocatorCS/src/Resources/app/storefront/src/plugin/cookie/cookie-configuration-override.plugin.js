// this event will be published via a global (document) EventEmitter
export const ACRIS_STORE_LOCATOR_COOKIE_CONFIGURATION_CHANGED = 'AcrisStoreLocatorCookieConfiguration_Change';

const CookieConfigurationPlugin = PluginManager.getPlugin('CookieConfiguration').get('class');

import deepmerge from 'deepmerge';

export default class CookieConfigurationOverride extends CookieConfigurationPlugin {

    static options = deepmerge(CookieConfigurationPlugin.options, {
        defaultCookie: 'store-locator-cookie'
    });

    _handleCheckbox(event) {
        const { target } = event;

        if (target && target.dataset && target.dataset.cookie && target.dataset.cookie === this.options.defaultCookie) {
            document.$emitter.publish(ACRIS_STORE_LOCATOR_COOKIE_CONFIGURATION_CHANGED, target.checked);
        }

        super._handleCheckbox(event);
    }
}
