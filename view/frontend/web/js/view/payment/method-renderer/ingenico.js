/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'uiLayout',
        'Magento_Checkout/js/action/redirect-on-success',
        'Netresearch_Epayments/js/model/payment/products',
        'Netresearch_Epayments/js/model/payment/groups',
        'Netresearch_Epayments/js/action/get-payment-product',
        'Netresearch_Epayments/js/action/create-payload',
        'Netresearch_Epayments/js/action/preload-components',
        'Netresearch_Epayments/js/model/payment/config',
        'Magento_Checkout/js/model/quote',
        'Netresearch_Epayments/js/model/payment/payment-data',
        'Netresearch_Epayments/js/model/validation/product-field',
        'Netresearch_Epayments/js/model/validation/ingenico'
    ],
    function ($, ko, Component, layout, redirectOnSuccessAction, productList,
              productGroups, fetchProduct, createPayload, preloadComponents,
              config, quote, paymentData, fieldValidator, ingenicoValidator
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Netresearch_Epayments/payment/ingenico',
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
                let id, label;

                if (paymentData.getCurrentPaymentProduct()) {
                    id = paymentData.getCurrentPaymentProduct().id;
                    label = paymentData.getCurrentPaymentProduct().displayHints.label;
                }
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'ingenico_payment_product_id': id,
                        'ingenico_payment_product_label': label,
                        'ingenico_payment_product_tokenize': paymentData.tokenize().indexOf(id) !== -1,
                        'ingenico_payment_product_method': paymentData.getCurrentPaymentProduct().paymentMethod,
                        'ingenico_payment_payload': paymentData.getCurrentPayload(),
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

                data['paymentProduct'] = paymentData.getCurrentPaymentProduct();
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
                        template: 'Netresearch_Epayments/payment/redirect-notice',
                        text: 'You can select your payment product in the next step.',
                    });
                } else {
                    components.push({
                        displayArea: 'ingenico-product-groups',
                        parent: this.name,
                        dataScope: this.name,
                        component: 'Netresearch_Epayments/js/view/payment/component/collection/groups',
                        productGroups: productGroups.groups,
                    });
                }

                layout(components);

                return this;
            },
        });
    }
);
