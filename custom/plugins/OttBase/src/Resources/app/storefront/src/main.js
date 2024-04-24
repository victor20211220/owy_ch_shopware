import OttTableSortablePlugin from './plugin/ott-table-sortable/ott-table-sortable.plugin';
import OttCustomSelectPlugin from './plugin/custom-select/ott-custom-select.plugin';
import OttElementMarkerPlugin from './plugin/element/ott-element-marker.plugin';
import OttBsHoverDropdownPlugin from './plugin/dropdown/ott-bs-hover-dropdown.plugin';
import OttHistoryJumpPlugin from './plugin/history/ott-history-jump.plugin';
import OttTwoFadingPlugin from './plugin/ott-fading/ott-two-fading.plugin';
import OttTabsPlugin from './plugin/tabs/ott-tabs.plugin';
import OttSelectToRadioConverterPlugin from './plugin/element/ott-select-to-radio-converter.plugin';

const pluginManager = window.PluginManager;

pluginManager.register(
    'OttTableSortable',
    OttTableSortablePlugin,
    '.ott-table-sortable, [data-ott-table-sortable=true]',
);
pluginManager.register('OttCustomSelect', OttCustomSelectPlugin, '[data-ott-custom-select=true]');
pluginManager.register('OttElementMarker', OttElementMarkerPlugin, '[data-ott-element-marker=true]');
pluginManager.register('OttBsHoverDropdown', OttBsHoverDropdownPlugin, '[data-ott-bs-hover-dropdown=true]');
pluginManager.register('OttHistoryJump', OttHistoryJumpPlugin, '[data-ott-history-jump=true]');
pluginManager.register('OttTwoFading', OttTwoFadingPlugin, '[data-ott-two-fading=true]');
pluginManager.register(
    'OttSelectToRadioConverter',
    OttSelectToRadioConverterPlugin,
    '[data-ott-select-to-radio-converter=true]',
);

if (!('OttTabs' in pluginManager.getPluginList())) {
    pluginManager.register('OttTabs', OttTabsPlugin, '[data-ott-tabs=true]');
}
