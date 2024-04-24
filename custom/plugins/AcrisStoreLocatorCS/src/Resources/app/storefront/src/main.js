import AcrisStoreLocatorPlugin from './plugin/acris-store-locator/acris-store-locator.plugin';
import AcrisStoreLocatorDetailPlugin from './plugin/acris-store-locator/acris-store-locator-detail.plugin';
import CookieConfigurationOverride from './plugin/cookie/cookie-configuration-override.plugin';
import AcrisFormValidationHelperPlugin from "./plugin/form-validation/acris-form-validation-helper.plugin";

window.PluginManager.register('AcrisStoreLocator', AcrisStoreLocatorPlugin, '[data-acris-store-locator]');
window.PluginManager.register('AcrisStoreLocatorDetail', AcrisStoreLocatorDetailPlugin, '[data-acris-store-locator-detail]');
window.PluginManager.override('CookieConfiguration', CookieConfigurationOverride, '[data-cookie-permission]')
window.PluginManager.register('AcrisFormValidationHelper', AcrisFormValidationHelperPlugin, '[data-acris-form-validation-helper]');