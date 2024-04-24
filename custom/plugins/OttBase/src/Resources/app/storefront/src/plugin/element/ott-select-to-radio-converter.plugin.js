import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import Iterator from 'src/helper/iterator.helper';

export default class OttSelectToRadioConverterPlugin extends Plugin {
    init() {
        this.selectElement = DomAccess.querySelector(this.el, 'select');
        this.el.classList.add('OttRadioSelect');

        this.createRadioButtons();
        this.selectFirstRadioButton();
        this.selectElement.remove();
    }

    createRadioButtons() {
        this.radioWrapper = document.createElement('div');
        this.radioWrapper.classList.add('OttRadioSelect__radio-wrapper');

        const options = DomAccess.querySelectorAll(this.el, 'option');

        Iterator.iterate(options, option => {
            if (!option.disabled) {
                this.radioWrapper.appendChild(
                    this.createRadioButton(
                        option.innerText.trim(),
                        option.value,
                        this.el.attributes.value.value === option.value
                    ),
                );
            }
        });

        this.el.appendChild(this.radioWrapper);
    }

    createRadioButton(label, value, selected) {
        const input = document.createElement('input');
        input.setAttribute('type', 'radio');
        input.setAttribute('name', this.selectElement.getAttribute('name'));
        input.setAttribute('value', value);
        input.classList.add('is--hidden');

        if (selected) {
            input.setAttribute('checked', selected);
        }

        const radioButton = document.createElement('div');
        radioButton.classList.add('OttRadioSelect__radio-btn');
        radioButton.innerHTML = label;

        const labelElement = document.createElement('label');
        labelElement.appendChild(input);
        labelElement.appendChild(radioButton);

        return labelElement;
    }

    selectFirstRadioButton() {
        if (!DomAccess.querySelectorAll(this.radioWrapper, 'input:checked', false).length) {
            DomAccess.querySelectorAll(this.radioWrapper, 'input')[0].setAttribute('checked', true);
        }
    }
}
