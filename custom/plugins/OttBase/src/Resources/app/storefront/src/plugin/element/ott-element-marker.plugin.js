import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import Iterator from 'src/helper/iterator.helper';

export default class OttElementMarkerPlugin extends Plugin {
    static options = {
        type: 'class',
        value: 'js-ott-element-marker',
        unique: true,
        remoteTargetValue: '',
        action: 'add',
    };

    init() {
        this.setValueSelector();
        this.registerEvents();
    }

    setValueSelector() {
        if ('class' === this.options.type) {
            this.valueSelector = `.${this.options.value}`;
        }
    }

    registerEvents() {
        this.el.addEventListener('click', this.onClick.bind(this));
    }

    onClick() {
        let target = this.el;

        if ('' !== this.options.remoteTargetValue) {
            const remoteTarget = DomAccess.querySelector(document, this.options.remoteTargetValue, false);

            if (remoteTarget) {
                target = remoteTarget;
            }
        }

        if ('class' === this.options.type) {
            target.classList[this.options.action] && target.classList[this.options.action](this.options.value);
        }

        if (this.options.unique) {
            const allMarkers = DomAccess.querySelectorAll(document, this.valueSelector, false);

            if (allMarkers) {
                Iterator.iterate(allMarkers, marker => {
                    if ('class' === this.options.type && target !== marker) {
                        marker.classList.remove(this.options.value);
                    }
                });
            }
        }
    }
}
