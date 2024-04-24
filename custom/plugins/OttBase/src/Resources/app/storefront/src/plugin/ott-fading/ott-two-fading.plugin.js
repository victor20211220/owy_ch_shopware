import Plugin from 'src/plugin-system/plugin.class';
import FadeHelper from '@FadeHelper';
import DomAccess from 'src/helper/dom-access.helper';

export default class OttTwoFadingPlugin extends Plugin {
    static options = {
        fadeOutElementSelector: '[data-ott-two-fading-out-element=true]',
        fadeInElementSelector: '[data-ott-two-fading-in-element=true]',
    }

    init() {
        this.setReferences();
        this.registerEvents();
    }

    setReferences() {
        this.fadeOutElement = DomAccess.querySelector(document, this.options.fadeOutElementSelector);
        this.fadeInElement = DomAccess.querySelector(document, this.options.fadeInElementSelector);
    }

    registerEvents() {
        this.el.addEventListener('click', this.fadeInAndOut.bind(this));
    }

    fadeInAndOut() {
        FadeHelper.fadeOut(this.fadeOutElement).then(() => {
            FadeHelper.fadeIn(this.fadeInElement);
        })
    }
}
