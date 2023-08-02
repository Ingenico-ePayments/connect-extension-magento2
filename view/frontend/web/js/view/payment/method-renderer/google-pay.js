define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/redirect-on-success',
    'Worldline_Connect/js/model/payment/payment-data',
    'Worldline_Connect/js/action/get-payment-product',
    'Worldline_Connect/js/model/client',
    'Worldline_Connect/js/model/payment/config',
    'google-pay-sdk',
], function ($, Component, quote, redirectOnSuccessAction, paymentData, getPaymentProduct, client, config) {
    'use strict';

    return Component.extend({
        config: {},
        defaults: {
            googlePayPaymentRequest: null,
            code: '',
            product: null,
            networks: null,
            merchantId: null,
            environment: null,
            template: 'Worldline_Connect/payment/product/google-pay',
            renderGooglePay: function (element) {
                let me = this;

                me.code = this.getCode();
                me.product = window.checkoutConfig.payment.worldline.products[me.code];

                getPaymentProduct(me.product.id).then(function(product) {
                    const googlePayClient = new google.payments.api.PaymentsClient({
                        environment: window.checkoutConfig.payment.worldline.googlePay.environment
                    });
                    const baseRequest = me.buildPaymentRequest(product);
                    const allowedPaymentProducts = me.buildAllowedPaymentMethods(product);

                    const isReadyToPayRequest = Object.assign({}, baseRequest);
                    isReadyToPayRequest.allowedPaymentMethods = allowedPaymentProducts;

                    googlePayClient.isReadyToPay(isReadyToPayRequest).then(function (response) {
                        if (response.result) {
                            const button = googlePayClient.createButton({
                                buttonColor: window.checkoutConfig.payment.worldline.googlePay.buttonColor,
                                buttonType: window.checkoutConfig.payment.worldline.googlePay.buttonType !== 'pay' ? window.checkoutConfig.payment.worldline.googlePay.buttonType : undefined,
                                buttonSizeMode: window.checkoutConfig.payment.worldline.googlePay.buttonSizeMode,
                                buttonLocale: window.checkoutConfig.payment.worldline.googlePay.buttonLocale,
                                onClick: function() {
                                    const paymentDataRequest = Object.assign({}, baseRequest);
                                    paymentDataRequest.allowedPaymentMethods = allowedPaymentProducts;

                                    googlePayClient.loadPaymentData(paymentDataRequest).then(function (googlePaymentData) {
                                        let sdkClient = client.initialize();
                                        let encryptor = sdkClient.getEncryptor();
                                        let request = sdkClient.getPaymentRequest();

                                        request.setPaymentProduct(product);
                                        request.setValue('encryptedPaymentData', googlePaymentData.paymentMethodData.tokenizationData.token);

                                        encryptor.encrypt(request).then(function (payload) {
                                            paymentData.setToken(googlePaymentData.paymentMethodData.tokenizationData.token);
                                            paymentData.setCurrentPayload(payload);
                                            paymentData.setCurrentPaymentProduct(product);
                                            paymentData.setCurrentProductIdentifier('product-320');

                                            me.placeOrder();
                                        }, function (error) {
                                            console.error('Could not create payload.', error);
                                            alert('Payment error: ' + error);
                                        })
                                    }).catch(function (err) {
                                        console.error(err);
                                    });
                                }
                            });

                            element.appendChild(button);
                        }
                    })
                });
            },
        },

        buildAllowedPaymentMethods: function (product) {
            return [{
                tokenizationSpecification: {
                    type: 'PAYMENT_GATEWAY',
                    parameters: {
                        'gateway': product.paymentProduct320SpecificData.json.gateway,
                        'gatewayMerchantId': window.checkoutConfig.payment.worldline.merchantId
                    }
                },
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                    allowedCardNetworks: product.paymentProduct320SpecificData.json.networks
                }
            }];
        },

        buildPaymentRequest: function() {
            return {
                apiVersion: 2,
                apiVersionMinor: 0,
                transactionInfo: {
                    countryCode: quote.billingAddress().countryId,
                    currencyCode: quote.getTotals()()['base_currency_code'],
                    totalPriceStatus: 'FINAL',
                    totalPrice: String(Math.round(parseFloat(quote.getTotals()()['base_grand_total']) * 100).toFixed(0) / 100)
                },
                merchantInfo: {
                    merchantId: window.checkoutConfig.payment.worldline.googlePay.merchantId,
                    merchantName: window.checkoutConfig.payment.worldline.googlePay.merchantName
                }
            };
        },

        getData: function () {
            let product = window.checkoutConfig.payment.worldline.products[this.code];
            return {
                'method': this.item.method,
                'additional_data': {
                    'input': paymentData.getCurrentPayload(),
                    'token': paymentData.getToken(),
                    'product': product.id,
                }
            };
        },

        afterPlaceOrder: function () {
            if (paymentData.getCurrentPayload()) {
                redirectOnSuccessAction.redirectUrl = config.getInlineSuccessUrl();
            } else {
                redirectOnSuccessAction.redirectUrl = config.getHostedCheckoutUrl();
            }
            this.redirectAfterPlaceOrder = true;
        },

        initialize: function () {
            this._super();

            this.initChildren();

            return this;
        }
    })
});
