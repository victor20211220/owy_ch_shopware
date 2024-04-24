import Plugin from 'src/plugin-system/plugin.class';
import DeviceDetection from 'src/helper/device-detection.helper';
import AjaxOffcanvas from 'src/plugin/offcanvas/ajax-offcanvas.plugin';
import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';
import CompareLocalStorageHelper from '../helper/compare-local-storage.helper';

export default class CompareFloatPlugin extends Plugin {
    static options = {
        buttonSelector: '.js-compare-float-button'
    };

    init() {
        this._button = this.el.querySelector(this.options.buttonSelector);
        this._defaultPadding = window.getComputedStyle(this._button).getPropertyValue('bottom');
        this._badge = this._button.querySelector('.badge');

        this._updateButtonCounter(CompareLocalStorageHelper.getAddedProductsList());
        this._addBodyPadding();
        this._registerEvents();
    }

    _updateButtonCounter(products) {
        const count = products.length;
        this._badge.innerText = count;

        if (count === 0) {
            this._button.classList.remove('is-visible');
        } else if (!this._button.classList.contains('is-visible')) {
            this._button.classList.add('is-visible');
        }
    }

    /**
     * registers all needed events
     *
     * @private
     */
    _registerEvents() {
        const submitEvent = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click';

        if (this._button) {

            this._button.addEventListener(submitEvent, () => {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                if($('.compare-menu-btn').hasClass('mobile-compare')){

                    //window.location.replace("https://test.owy.ch/compare");

                    var owyLink = $('#owyshopLink').attr('data-shop-url');

                    window.location.replace(owyLink + "/compare");

                }else{
                    if ($('.header-wishlist').children().children().hasClass('show')){
                        $('.header-wishlist').children().children().removeClass('show');
                        var removeWLshowClass = $('.header-wishlist').children()[1];
                        $(removeWLshowClass).removeClass('show');

                    }
                    const data = {
                        productIds: CompareLocalStorageHelper.getAddedProductsList(),
                    };
                    if($('.dropdown-menu').hasClass('owy-compare-inner-cart show')){
                        $('.dropdown-menu').removeClass('owy-compare-inner-cart show');
                    }else{
                        $.post(window.router['frontend.compare.offcanvas'], JSON.stringify(data), function(response){

                            if(response.status && response.productCount > 0) {
                                $('.owy-compare-cart').find('.dropdown-menu').addClass('owy-compare-inner-cart show');
                                $('.owy-compare-cart').find('.dropdown-menu').html(response.result);
                            }else{
                                var owyLink = $('#owyshopLink').attr('data-shop-url');
                                window.location.replace(owyLink + "/compare");
                            }
                        });
                    }
                }



                //this._openOffcanvas();
                this.$emitter.publish('onClickCompareFloatButton');
            });
        }

        document.$emitter.subscribe('beforeAddProductCompare', event => {
            const { productCount } = event.detail;

            if (productCount >= CompareLocalStorageHelper.maximumCompareProducts) {
                const pseudoModal = new PseudoModalUtil(this.options.maximumNumberCompareProductsText);

                pseudoModal.open();
            }
        });

        document.$emitter.subscribe('changedProductCompare', event => {
            this._updateButtonCounter(event.detail.products);
        });

        document.addEventListener('scroll', this._debouncedOnScroll, false);
        const observer = new MutationObserver(this._addBodyPadding.bind(this));
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['style']
        });
    }

    _addBodyPadding() {
        this._button.style.bottom = `calc(${this._defaultPadding} + ${document.body.style.paddingBottom || '0px'})`;
    }

    _openOffcanvas() {
        const data = {
            productIds: CompareLocalStorageHelper.getAddedProductsList(),
        };

        AjaxOffcanvas.open(window.router['frontend.compare.offcanvas'], JSON.stringify(data), (response) => {
            this.$emitter.publish('insertStoredContent', { response });
        });
    }
}
