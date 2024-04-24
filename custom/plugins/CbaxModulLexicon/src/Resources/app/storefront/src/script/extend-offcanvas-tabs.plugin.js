import ViewportDetection from 'src/helper/viewport-detection.helper';
import OffcanvasTabsPlugin from 'src/plugin/offcanvas-tabs/offcanvas-tabs.plugin';

export default class ExtendOffcanvasTabsPlugin extends OffcanvasTabsPlugin {

    _onClickOffCanvasTab(event) {
        super._onClickOffCanvasTab(event);

        if (ViewportDetection.isXS()) {

            let linkedWords = document.querySelectorAll('div.offcanvas span.lexicon-modal');

            if (linkedWords.length > 0) {

                linkedWords.forEach(function(span) {

                    let modalLink = span.firstChild;
                    let linkText = document.createTextNode(modalLink.firstChild.data);
                    let newLink = document.createElement("a");

                    newLink.classList.add('lexicon-tooltip');
                    newLink.classList.add('cbax-lexicon-link');
                    newLink.setAttribute('data-bs-toggle', 'tooltip');
                    newLink.setAttribute('data-bs-placement', 'top');
                    newLink.setAttribute('data-bs-html', 'true');
                    newLink.setAttribute('data-bs-template', "<div class='tooltip' role='tooltip'><div class='arrow'></div><div class='tooltip-inner cbax-lexicon-tooltip-inner'></div></div>");
                    newLink.appendChild(linkText);
                    newLink.setAttribute('title', modalLink.getAttribute('data-original-title'));

                    span.replaceChild(newLink, modalLink);
                })
            }
        }
    }
}
//Alternativ über event onClickOffCanvasTab:
//let tab = document.querySelector('[data-offcanvas-tabs]');
//             if (tab) {
//                 tab.$emitter.subscribe('onClickOffCanvasTab', () => {  ...

