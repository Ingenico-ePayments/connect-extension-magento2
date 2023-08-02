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
                template: 'Worldline_Connect/payment/product/apple-pay',
                renderApplePay: function (element) {
                    let me = this;
                    let applePayButton = document.createElement('apple-pay-button');
                    applePayButton.setAttribute('locale', window.checkoutConfig.payment.worldline.applePay.buttonLocale);
                    applePayButton.setAttribute('buttonstyle', window.checkoutConfig.payment.worldline.applePay.buttonStyle);
                    applePayButton.setAttribute('type', window.checkoutConfig.payment.worldline.applePay.buttonType);
                    applePayButton.addEventListener('click', function (e) {
                        if (!window.ApplePaySession) {
                            return;
                        }

                        let sdkClient = client.initialize();
                        let totalAmount = Math.round(parseFloat(quote.getTotals()()['base_grand_total']) * 100).toFixed(0)
                        let countryCode = quote.billingAddress().countryId;
                        let currencyCode = quote.getTotals()()['base_currency_code'];
                        let payload = {
                            totalAmount: totalAmount,
                            countryCode: countryCode,
                            currency: currencyCode,
                            displayName: 'TEST'
                        };

                        sdkClient.createApplePayPayment(payload, { merchantName: 'TEST'}, me.product.paymentProduct302SpecificData.json.networks).then(function (token) {
                            let encryptor = sdkClient.getEncryptor();
                            let request = sdkClient.getPaymentRequest();
                            let tokenString = JSON.stringify(token.data.paymentData);

                            request.setPaymentProduct(me.product);
                            request.setValue('encryptedPaymentData', tokenString);
                            encryptor.encrypt(request).then(function (payload) {
                                paymentData.setToken(tokenString);
                                paymentData.setCurrentPayload(payload);
                                paymentData.setCurrentPaymentProduct(me.product);
                                paymentData.setCurrentProductIdentifier('product-302');

                                me.placeOrder();
                            }, function (error) {
                                console.error('Could not create payload.', error);
                                alert('Payment error: ' + error);
                            });
                        }, function (error) {
                            alert('Payment error: ' + error);
                        })
                    });

                    element.appendChild(applePayButton);
                }
            },
            initialize: function () {
                this._super();

                this.initChildren();

                return this;
            },
            initChildren: function () {

                let me = this;
                me.code = this.getCode();
                getPaymentProduct(window.checkoutConfig.payment.worldline.products[me.code].id).then(function(product) {
                    me.product = product;
                });
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
        })
    }
);
