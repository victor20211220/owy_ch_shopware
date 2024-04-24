import Plugin from 'src/plugin-system/plugin.class';
import customSelect from '@custom-select';
import Iterator from 'src/helper/iterator.helper';
import DomAccess from 'src/helper/dom-access.helper';

export default class OttCustomSelectPlugin extends Plugin {
    static options = {
        additionalClasses: null,
        prefixContentCls: 'has-prefix-content',
        prefixContent: null,
        appendixContentCls: 'has-appendix-content',
        appendixContent: null,
        triggerFormChange: false,
        triggerFormChangeOnlyOnValueChange: true,
        customSelectConfig: {
            containerClass: 'OttCustomSelect',
            openerClass: 'OttCustomSelect__opener',
            panelClass: 'OttCustomSelect__panel',
            panelInnerClass: 'OttCustomSelect__panel-inner',
            optionClass: 'OttCustomSelect__option',
            optgroupClass: 'OttCustomSelect__optgroup',
            isSelectedClass: 'is-selected',
            hasFocusClass: 'is-hover',
            isDisabledClass: 'is-disabled',
            isOpenClass: 'is-open',
        },
    };

    init() {
        this.customSelectConfig = this.options.customSelectConfig;
        this.customSelectPlugin = customSelect(this.el, this.customSelectConfig)[0];
        this.selectOptions = this.customSelectPlugin.panel.querySelectorAll(`.${this.customSelectConfig.optionClass}`);
        this.eventData = { element: this.el, plugin: this.customSelectPlugin };
        this.currentValue = this.customSelectPlugin.value;

        this.createOptionsWrapper();
        this.addPrefixContent();
        this.addAppendixContent();
        this.addAdditionalClasses();
        window.PluginManager.initializePlugins();
        this.registerEvents();
    }

    createOptionsWrapper() {
        if (this.selectOptions) {
            // Create wrapper for options
            const optionsWrapper = document.createElement('div');
            optionsWrapper.classList.add(this.customSelectConfig.panelInnerClass);
            this.customSelectPlugin.panel.insertBefore(optionsWrapper, this.selectOptions[0]);

            this.optionsWrapper = this.customSelectPlugin.panel.querySelector(
                `.${this.customSelectConfig.panelInnerClass}`,
            );

            // Move all options inside wrapper
            Iterator.iterate(this.selectOptions, option => {
                this.optionsWrapper.appendChild(option);
            });
        }
    }

    addPrefixContent() {
        if (this.options.prefixContent) {
            this.customSelectPlugin.opener.classList.add(this.options.prefixContentCls);
            this.customSelectPlugin.opener.innerHTML = this.customSelectPlugin.opener.innerHTML
                + this.options.prefixContent;
        }
    }

    addAppendixContent() {
        if (this.options.appendixContent) {
            this.customSelectPlugin.opener.classList.add(this.options.appendixContentCls);
            this.customSelectPlugin.opener.innerHTML = this.customSelectPlugin.opener.innerHTML
                + this.options.appendixContent;
        }
    }

    addAdditionalClasses() {
        if (this.options.additionalClasses) {
            Iterator.iterate(this.options.additionalClasses, additionalClassData => {
                const allTargets = this.customSelectPlugin.container.parentNode.querySelectorAll(
                    `.${this.customSelectConfig[additionalClassData.targetClass]}`,
                );

                Iterator.iterate(allTargets, target => {
                    const allAdditionalClasses = additionalClassData.additionalClasses.split(',');

                    Iterator.iterate(allAdditionalClasses, cls => {
                        target.classList.add(cls);
                    })
                });
            });
        }
    }

    registerEvents() {
        this.customSelectPlugin.container.addEventListener('custom-select:open', this.onOpen.bind(this));
        this.customSelectPlugin.container.addEventListener('custom-select:close', this.onClose.bind(this));
        this.customSelectPlugin.container.addEventListener('custom-select:disabled', this.onDisabled.bind(this));
        this.customSelectPlugin.container.addEventListener('custom-select:enabled', this.onEnabled.bind(this));
        this.customSelectPlugin.panel.addEventListener(
            'custom-select:focus-outside-panel',
            this.onFocusedOptionOutsidePanel.bind(this)
        );
        this.customSelectPlugin.select.addEventListener('change', this.onChange.bind(this));
    }

    onOpen() {
        this.$emitter.publish('OttCustomSelect/onOpen', this.eventData);
    }

    onClose() {
        this.$emitter.publish('OttCustomSelect/onClose', this.eventData);
    }

    onDisabled() {
        this.$emitter.publish('OttCustomSelect/onDisabled', this.eventData);
    }

    onEnabled() {
        this.$emitter.publish('OttCustomSelect/onEnabled', this.eventData);
    }

    onFocusedOptionOutsidePanel() {
        this.$emitter.publish('OttCustomSelect/onFocusedOptionOutsidePanel', this.eventData);
    }

    onChange() {
        this.$emitter.publish('OttCustomSelect/onChangeBefore', this.eventData);

        const valueHasChanged = this.currentValue !== this.customSelectPlugin.value;

        if (valueHasChanged) {
            this.$emitter.publish('OttCustomSelect/onValueChange', this.eventData);
        }

        if (this.options.triggerFormChange) {
            let triggerFormChange = true;

            if (this.options.triggerFormChangeOnlyOnValueChange) {
                triggerFormChange = valueHasChanged;
            }

            if (triggerFormChange) {
                const formId = DomAccess.getAttribute(this.customSelectPlugin.select, 'form', false);
                const form = formId
                    ? DomAccess.querySelector(document, `#${formId}`, false)
                    : this.customSelectPlugin.select.closest('form');

                form && form.dispatchEvent(new Event('change'));
            }
        }

        this.currentValue = this.customSelectPlugin.value;

        this.$emitter.publish('OttCustomSelect/onChangeAfter', this.eventData);
    }
}
