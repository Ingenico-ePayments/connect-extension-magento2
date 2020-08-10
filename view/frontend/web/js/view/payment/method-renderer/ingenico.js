/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'uiLayout',
        'Magento_Checkout/js/action/redirect-on-success',
        'Ingenico_Connect/js/model/payment/products',
        'Ingenico_Connect/js/model/payment/groups',
        'Ingenico_Connect/js/action/get-payment-product',
        'Ingenico_Connect/js/action/create-payload',
        'Ingenico_Connect/js/action/preload-components',
        'Ingenico_Connect/js/model/payment/config',
        'Magento_Checkout/js/model/quote',
        'Ingenico_Connect/js/model/payment/payment-data',
        'Ingenico_Connect/js/model/validation/product-field',
        'Ingenico_Connect/js/model/validation/ingenico'
    ],
    function ($, ko, Component, layout, redirectOnSuccessAction, productList,
              productGroups, fetchProduct, createPayload, preloadComponents,
              config, quote, paymentData, fieldValidator, ingenicoValidator
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ingenico_Connect/payment/ingenico',
            },

            currentBillingCountry: '',
            currentBaseGrandTotal: 0,

            paymentProductGroups: productGroups.groups,

            availablePaymentProducts: productList.basicPaymentProducts,

            config: config,

            /**
             * Ingenico payment method component initializer
             *
             * @return {exports}
             */
            initialize: function () {
                this._super();
                this.currentBillingCountry = quote.billingAddress() ? quote.billingAddress().countryId : '';
                this.currentBaseGrandTotal = quote.totals()['base_grand_total'];
                config.init(window.checkoutConfig.payment[this.getCode()]);
                if (!config.useFullRedirect()) {
                    this.initializeProductList();
                }

                return this;
            },

            /**
             * Load and render payment product list.
             *
             * @private
             */
            initializeProductList: function () {
                this.initLoader();
                fieldValidator.register();
                productList.isLoading.subscribe(this.toggleLoader.bind(this));
                this.initProductList();
                preloadComponents();
            },

            getData: function () {
                let id,
                    label,
                    paymentMethod,
                    currentPaymentMethodProduct,
                    tokenize;

                currentPaymentMethodProduct = paymentData.getCurrentPaymentProduct();
                tokenize = paymentData.tokenize().indexOf(id) !== -1;

                if (currentPaymentMethodProduct) {
                    if (paymentData.getCurrentPaymentProduct().id === 'cards'
                        && paymentData.getCurrentCardPaymentProduct()) {
                        currentPaymentMethodProduct = paymentData.getCurrentCardPaymentProduct();
                        tokenize = paymentData.tokenize().indexOf('cards') !== -1;
                    }

                    id = currentPaymentMethodProduct.id;
                    label = currentPaymentMethodProduct.displayHints.label;
                    paymentMethod = currentPaymentMethodProduct.paymentMethod;
                }

                return {
                    'method': this.item.method,
                    'additional_data': {
                        'ingenico_payment_product_id': id,
                        'ingenico_payment_product_label': label,
                        'ingenico_payment_product_tokenize': tokenize,
                        'ingenico_payment_product_method': paymentMethod,
                        'ingenico_payment_payload': paymentData.getCurrentPayload(),
                        'ingenico_payment_is_payment_account_on_file': window.checkoutConfig.isPaymentAccountOnFile === true,
                    }
                };
            },

            afterPlaceOrder: function () {
                if (paymentData.getCurrentPayload()) {
                    redirectOnSuccessAction.redirectUrl = this.config.getInlineSuccessUrl();
                } else {
                    redirectOnSuccessAction.redirectUrl = this.config.getHostedCheckoutUrl();
                }
                this.redirectAfterPlaceOrder = true;
            },

            placeOrder: function (data, event) {
                let parentMethod = this._super.bind(this);

                if (!this.validate()) {
                    return false;
                }

                if (config.useInlinePayments()) {
                    this.createPayload().then(function () {
                        parentMethod(data, event);
                    }, function (error) {
                        console.error('Could not create payload.', error);
                        alert('Payment error: ' + error);
                    });
                } else {
                    parentMethod(data, event);
                }
            },

            /**
             * @public
             * @return {boolean}
             */
            validate: ingenicoValidator.validate,

            /**
             * @private
             */
            createPayload: function () {
                let data = this.assemblePayloadData();

                return createPayload(data).then(function (payload) {
                    paymentData.setCurrentPayload(payload);
                });
            },

            /**
             * @private
             * @return {{}}
             */
            assemblePayloadData: function () {
                let data = {};

                let currentPaymentProduct = paymentData.getCurrentPaymentProduct();
                if (currentPaymentProduct.id === 'cards') {
                   currentPaymentProduct = paymentData.getCurrentCardPaymentProduct();
                }

                data['paymentProduct'] = currentPaymentProduct;
                if (paymentData.getCurrentAccountOnFile()) {
                    data['accountOnFile'] = paymentData.getCurrentAccountOnFile();
                }
                data = Object.assign(paymentData.fieldData, data);

                return data;
            },

            /**
             * @public
             */
            isAvailable: function () {
                return this.availablePaymentProducts().length > 0;
            },

            /**
             * @private
             */
            initLoader: function () {
                this.loader = $("body");
            },

            /**
             * @private
             */
            initProductList: function () {
                /**
                 * Refresh product groups when product list changes
                 */
                productList.basicPaymentProducts.subscribe(function () {
                    productGroups.reload();
                });

                /**
                 * Refresh product list when billing country changes.
                 */
                quote.billingAddress.subscribe(function (address) {
                    if (quote.paymentMethod() && quote.paymentMethod().method !== this.getCode()) {
                        return;
                    }
                    if (!address || $.isEmptyObject(address)) {
                        return;
                    }
                    let billingCountry = address.countryId;

                    if (billingCountry != this.currentBillingCountry) {
                        this.currentBillingCountry = billingCountry;
                        productList.reload(this.messageContainer);
                    }
                }.bind(this));

                quote.totals.subscribe(function (totalsObject) {
                    if (this.currentBaseGrandTotal !== totalsObject['base_grand_total']) {
                        this.currentBaseGrandTotal = totalsObject['base_grand_total'];
                        productList.reload(this.messageContainer);
                    }
                }.bind(this));


                /**
                 * Refresh product list
                 */
                productList.reload(this.messageContainer);
            },

            /**
             * @private
             */
            toggleLoader: function (isLoading) {
                if (isLoading) {
                    this.loader.trigger('processStart');
                } else {
                    this.loader.trigger('processStop');
                }
            },

            /**
             * @private
             */
            initChildren: function () {
                this._super();
                let components = [];
                config.init(window.checkoutConfig.payment[this.getCode()]);
                if (config.useFullRedirect()) {
                    components.push({
                        displayArea: 'ingenico-product-groups',
                        parent: this.name,
                        component: 'uiElement',
                        template: 'Ingenico_Connect/payment/redirect-notice',
                        text: 'You can select your payment product in the next step.',
                    });
                } else {
                    if (config.groupCardPaymentMethods()) {
                        components.push({
                            displayArea: 'ingenico-product-groups',
                            parent: this.name,
                            dataScope: this.name,
                            component: 'Ingenico_Connect/js/view/payment/component/card/group',
                            cardGroupPaymentMethod: productList.cardGroupPaymentMethod,
                        })
                    }

                    components.push({
                        displayArea: 'ingenico-product-groups',
                        parent: this.name,
                        dataScope: this.name,
                        component: 'Ingenico_Connect/js/view/payment/component/collection/groups',
                        productGroups: productGroups.groups,
                    });
                }

                layout(components);

                return this;
            },
        });
    }
);
