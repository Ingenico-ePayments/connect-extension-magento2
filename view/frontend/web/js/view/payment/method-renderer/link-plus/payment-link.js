/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'Worldline_Connect/js/model/client',
        'Worldline_Connect/js/model/locale-resolver',
        'Worldline_Connect/js/model/payment/config',
        'Worldline_Connect/js/action/get-payment-product',
        'connectsdk.core',
        'connectsdk.ApplePay',
        'connectsdk.C2SCommunicator',
        'apple-pay-sdk',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Worldline_Connect/js/model/payment/payment-data',
    ],
    function ($, client, localeResolver, config, getPaymentProduct, connectSDK, ApplePay, C2SCommunicator, applePaySDK, Component, quote, paymentData) {
        return Component.extend({
            config: {},
            defaults: {
                buttonstyle: 'white-outline',
                applePayPaymentRequest: null,
                code: '',
                currentCountry: '',
                currentCurrencyCode: '',
                currentBaseGrandTotal: '',
                defaultGroupTitle: 'Select a new payment method',
                product: null,
                template: 'Worldline_Connect/payment/product/link-plus/payment-link'
            },
            initialize: function () {
                this._super();

                this.initChildren();

                return this;
            },
            initChildren: function () {
                let code = this.getCode();
                this.code = code;
            },
            getData: function () {
                return {
                    'method': this.item.method
                };
            },
        })
    }
);
