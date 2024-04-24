import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import DeviceDetection from 'src/helper/device-detection.helper';


export default class AcrisFormValidationHelperPlugin extends Plugin {

    static options = {
        inputId: '',

    };

    init() {
        if (this.options.inputId) {
            this.submitButton = DomAccess.querySelector(this.el, '#confirmFormSubmit', false);
            this.fieldToValidate = DomAccess.querySelector(document, '#' + this.options.inputId, false);

            if (this.submitButton && this.fieldToValidate) {
                this.fieldToValidate.classList.remove('validated')
                this._registerSubmitButtonEvent();
            }

        }
    }

    _registerSubmitButtonEvent() {
        const event = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click';
        this.submitButton.addEventListener(event, this.setValidationClass.bind(this));
    }

    setValidationClass() {
        this.fieldToValidate.classList.add('validated')
    }

}
