/* eslint-disable import/no-unresolved */

// noinspection NpmUsedModulesInstalled
import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';


class WalleeCheckoutPlugin extends Plugin {

    static options = {
        payment_method_tabs: 'ul.wallee-payment-panel li',
        payment_method_iframe_prefix: 'iframe_payment_method_',
        payment_method_iframe_class: '.wallee-payment-iframe',
        payment_method_handler_name: 'cwb_wallee_payment6_handler',
        payment_method_handler_prefix: 'wallee_handler_',
        payment_method_handler_status: 'input[name="cwb_wallee_payment6_handler_validation_status"]',
        payment_form: 'confirmOrderForm',
    };

    init() {
        // @TODO Move JS to Plugin
        this._client = new HttpClient(window.accessKey);
    }

}

export default WalleeCheckoutPlugin;