import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import DeviceDetection from 'src/helper/device-detection.helper';

export default class AcrisStoreLocatorDetailPlugin extends Plugin {

    static options = {
        encryptedMail: false,
        storeId: '',
        mailClass: '.acris-store-locator-mail',
        mailTo: 'mailto:'
    };

    init() {
        this.registerEvents();
    }

    registerEvents() {
        if (this.options.encryptedMail) {
            const event = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click';
            this.mail = DomAccess.querySelectorAll(this.el, this.options.mailClass, false);

            if (this.mail && this.mail.length > 0) {
                for (let i = 0; i < this.mail.length; i++) {
                    this.mail[i].addEventListener(event, this._onSendingEmail.bind(this, this.mail[i]));
                }
            }
        }
    }

    _onSendingEmail(mail, event) {
        event.preventDefault();
        if (mail && mail.dataset && mail.dataset.mail) {
            let encodedData = mail.dataset.mail;
            let decodedData = encodedData.replace(/[a-zA-Z]/g, function(char){ //foreach character
                return String.fromCharCode( //decode string
                    (char<="Z"?90:122)>=(char=char.charCodeAt(0)+10)?char:char -26
                );
            });
            location.href = this.options.mailTo + decodedData;
        }
    }
}
