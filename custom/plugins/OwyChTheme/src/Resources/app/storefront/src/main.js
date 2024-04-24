import OwyCustomOffcanvasCart from './offcanvas-cart/owy-offcanvas-cart.plugin';
import OwyWishlist from './owywishlist/wishlist.plugin';
import './js/owl.carousel.min.js';

const PluginManager = window.PluginManager;
PluginManager.register('OwyWishlist', OwyWishlist, '[data-wishlist]');
if (screen.width > 991) {
    console.log('Greater than 500 desktop cart hit');
    PluginManager.override('OffCanvasCart', OwyCustomOffcanvasCart, '[data-offcanvas-cart]');
} else {
    console.log('Less than 500 mobile cart hit');
}