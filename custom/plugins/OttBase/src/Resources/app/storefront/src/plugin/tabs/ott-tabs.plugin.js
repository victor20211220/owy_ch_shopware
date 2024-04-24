import Plugin from 'src/plugin-system/plugin.class';
import Iterator from 'src/helper/iterator.helper';
import DomAccess from 'src/helper/dom-access.helper';

export default class OttTabsPlugin extends Plugin {
    init() {
        this.tabs = [];
        this.tabs.push(...DomAccess.querySelectorAll(this.el, '[data-tab-content-selector]'));

        this._registerEvents();
    }

    _registerEvents() {
        Iterator.iterate(this.tabs, tab => {
            tab.addEventListener('click', this.onTabClick.bind(this));
        });
    }

    onTabClick(event) {
        const clickedTab = event.currentTarget;

        // Set all tabs inactive
        Iterator.iterate(this.tabs, tab => {
            tab.classList.remove('active');
        });

        // Set current tab active
        clickedTab.classList.add('active');

        // Get tab content
        const tabContentSelector = DomAccess.getDataAttribute(clickedTab, 'tab-content-selector');
        const tabContent = DomAccess.querySelector(document, tabContentSelector);

        // Show tab content
        tabContent.classList.remove('d-none');

        // Hide all other tab contents
        const tabContentSiblings = [...tabContent.parentElement.children].filter(
            sibling => 1 === sibling.nodeType && sibling !== tabContent
        );

        Iterator.iterate(tabContentSiblings, sibling => {
            sibling.classList.add('d-none');
        });
    }
}
