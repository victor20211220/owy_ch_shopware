const PluginManager = window.PluginManager;

import ExtendOffcanvasTabsPlugin from "./script/extend-offcanvas-tabs.plugin";
import OffcanvasTabsPlugin from 'src/plugin/offcanvas-tabs/offcanvas-tabs.plugin';

PluginManager.register('OffcanvasTabs', OffcanvasTabsPlugin, '[data-offcanvas-tabs]');
PluginManager.override('OffcanvasTabs', ExtendOffcanvasTabsPlugin, '[data-offcanvas-tabs]');


// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
