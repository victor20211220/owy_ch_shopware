import ViewportDetection from 'src/helper/viewport-detection.helper';
import OffCanvasCartPlugin from 'src/plugin/offcanvas-cart/offcanvas-cart.plugin';
import AjaxOffCanvas from '../offcanvas/ajax-offcanvas.plugin';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import DeviceDetection from 'src/helper/device-detection.helper';

export default class OwyCustomOffcanvasCart extends OffCanvasCartPlugin {

    init() {
        const _this = this;
        $(document).on('click', '.btn-link', function (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            var owyLink = $('#owyshopLink').attr('data-shop-url');

            window.location.replace(owyLink + "/checkout/cart");


        });
        $(document).on('click', '#owy-remove', function () {
            var idToRemove = $(this).closest('.line-item-remove-button').data('id');
            var storedIDs = JSON.parse(localStorage.getItem('compare-widget-added-products'));
            var updatedIDs = storedIDs.filter(function (id) {
                return id !== idToRemove;
            });
            localStorage.setItem('compare-widget-added-products', JSON.stringify(updatedIDs));
            location.reload();
            event.stopPropagation();
        });
        $(document).on('click', ".deleteWishlist", function () {
            const deleteWishlist = $(this).data('delurl');
            const productId = $(this).data('prdid');
            var wishlistPrd = $(".product-wishlist-" + productId);
            if (!$(this).hasClass('deleted')) {
                $(this).addClass('deleted');
                $.post(deleteWishlist, function (data) {
                    getWishlist();
                    let wishlistCount = parseInt($("#wishlist-basket").text()) - 1;
                    $("#wishlist-basket").text(wishlistCount);
                    wishlistPrd.removeClass('product-wishlist-added');
                    wishlistPrd.addClass('product-wishlist-not-added');
                });
            }
        });

        async function getWishlist() {
            const wishlistUrl = $("#wishlistUrl").val();
            const customerId = $("#customerId").val();
            await $.post(wishlistUrl, { customerId: customerId }, function (data) {
                if (data.status) {
                    $('.header-wishlist').find(".dropdown-menu").removeClass('wishlist-login');
                    var checkWLClasshow = $('.header-wishlist').find(".dropdown").children()[0];
                    if ($(checkWLClasshow).hasClass('show')) {
                        setTimeout(() => {
                            var secondChildWishlist = $('.header-wishlist').find(".dropdown").children()[1];
                            $(secondChildWishlist).addClass('show');
                        }, 700);
                    }
                    $('.header-wishlist').find('.dropdown-menu').html(data.wishlist);
                    location.reload();
                } else {
                    if (data.wishlist != null) {
                        $('.header-wishlist').find(".dropdown-menu").addClass('wishlist-login');
                        $('.header-wishlist').find(".dropdown").removeClass('owy-wishlist-cart');
                        $('.header-wishlist').find('.dropdown-menu').html(data.wishlist);
                        location.reload();
                    }
                }
            });
        }

        super.init();
        $(document).on("click", function (event) {
            if ($(event.target).closest(".header-cart").length === 0) {
                if ($('#new-cart').hasClass('show')) {
                    $('#new-cart').addClass('d-none');
                    $('#new-cart').removeClass('show');
                    $("#cart-data").addClass('d-none');
                }
            }
        });
        $(document).on('click', '.header-cart', function (event) {
            if ($('.dropdown-menu').hasClass('show')) {
                $('.dropdown-menu').removeClass('owy-compare-inner-cart show');
            }
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            if ($('#new-cart').hasClass('d-none')) {
                $('#new-cart').removeClass('d-none');
                $('#new-cart').addClass('show');
                $("#cart-data").removeClass('d-none');
            }
        });

        if ($('body').hasClass('is-ctl-checkout is-act-cartpage') !== true) {
            $(document).on('click', '.line-item-remove-button', function (event) {
                ElementLoadingIndicatorUtil.create($(`#new-cart .owy-nav-cart`)[0]);
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                let form = $(this).parent();
                let action = $(form).attr('action');
                $.post(action, function () {
                    _this.reloadCart();
                });
            });
        }
        $(document).on('change', '.line-item-quantity-container ', function (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            let action = $(this).attr('action');
            let data = $(this).serialize();
            changeQty(action, data);
        });

        function changeQty(action, data) {
            $.post(action, data, function () {
                _this.reloadCart();
            });
        }
    }

    reloadCart(){
        this._onOpenOffCanvasCart(new Event(`click`));
    }


    /**
     * public method to open the offCanvas
     *
     * @param {string} url
     * @param {{}|FormData} data
     * @param {function|null} callback
     */
    openOffCanvas(url, data, callback) {
        // console.log(`event:`, event);
        //if($(event.target).closest(`.line-item-row`).length) return;
        const isFullwidth = ViewportDetection.isXS();
        AjaxOffCanvas.open(
            url, 
            data, 
            () => {
                // console.log(`start callback`);
                this._onOffCanvasOpened.call(this, callback);
                // console.log(`end callback`);
                var cartData = $("body").find('.offcanvas-cart');
                if (cartData.length > 1) {
                    cartData = cartData[cartData.length - 1];
                }
                $(cartData).removeClass("d-none");
                $("#cart-data").html(cartData).removeClass('d-none');
                if (data != false) {
                    $("#new-cart").removeClass('d-none').addClass(`show`);
                }
            },
            this.options.offcanvasPosition, 
            undefined, 
            undefined, 
            isFullwidth
        );
        AjaxOffCanvas.setAdditionalClassName(this.options.additionalOffcanvasClass);
    }

    /**
     * Register events to handle opening the Cart OffCanvas
     * by clicking a defined trigger selector
     *
     * @private
     */
    _registerOpenTriggerEvents() {
        const event = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click';
        console.log('register cart click event');
        this.el.querySelector(`a.header-cart-btn`).addEventListener(event, this._onOpenOffCanvasCart.bind(this));
    }
}