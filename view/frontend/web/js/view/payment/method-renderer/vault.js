/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Checkout/js/model/quote',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/model/payment/payment-data',
    'Ingenico_Connect/js/action/create-payload',
    'uiLayout',
    'uiRegistry',
    'ko'
], function ($, VaultComponent, quote, getPaymentProduct, paymentData, createPayload, layout, registry, ko) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            code: '',
            title: null,
            logo: null,
            currentCountry: '',
            product: null,
            maskedCard: null,
            template: 'Ingenico_Connect/payment/form'
        },

        initialize: function () {
            this._super();

            this.title = ko.observable('');
            this.logo = ko.observable('');
            this.maskedCard = ko.observable('');

            this.initChildren();

            return this;
        },

        initChildren: function () {
            this._super();

            let code = this.getCode();
            let name = this.name;
            let me = this;

            this.code = code;

            quote.billingAddress.subscribe(function (address) {
                if (!address || $.isEmptyObject(address)) {
                    return;
                }

                let billingCountry = address.countryId;
                if (billingCountry === me.currentCountry) {
                    return;
                }

                me.currentCountry = billingCountry;

                let product = window.checkoutConfig.payment.ingenico.products[code];
                if (!product) {
                    return;
                }

                getPaymentProduct(product.id).then(function(productResponse) {
                    me.title(productResponse.displayHints.label);
                    me.logo(productResponse.displayHints.logo);

                    let accounts = productResponse.accountsOnFile.filter(function (accountOnFile) {
                        return accountOnFile.attributeByKey['alias'].value === me.details.alias;
                    });

                    me.product = productResponse;
                    me.account = accounts.length > 0 ? accounts[0] : null;
                    if (me.account) {
                        me.maskedCard(me.account.getMaskedValueByAttributeKey('alias').formattedValue);
                    }

                    if (product.hosted) {
                        layout([{
                            component: 'Ingenico_Connect/js/view/payment/component/collection/hosted',
                            uid: 'ingenico-' + code + '-fields',
                            displayArea: 'ingenico-cc-fields',
                            parent: name,
                            template: 'Ingenico_Connect/payment/product/field-collection',
                            account: me.account
                        }]);
                    } else {
                        layout([{
                            component: 'Ingenico_Connect/js/view/payment/component/collection/fields-inline',
                            uid: 'ingenico-' + code + '-fields',
                            displayArea: 'ingenico-cc-fields',
                            parent: name,
                            template: 'Ingenico_Connect/payment/product/field-collection',
                            product: productResponse,
                            account: me.account
                        }]);
                    }
                });
            }.bind(this));

            return this;
        },

        /**
         * @private
         * @return {{}}
         */
        assemblePayloadData: function () {
            let data = paymentData.fieldData;
            data.paymentProduct = this.product;
            data.accountOnFile = this.account;

            return data;
        },

        /**
         * @private
         */
        createPayload: function () {
            let data = this.assemblePayloadData();


            return createPayload(data).then(function (payload) {
                paymentData.setCurrentPayload(payload);
            });
        },

        validate: function () {
            paymentData.fieldData = {};

            let product = window.checkoutConfig.payment.ingenico.products[this.code];
            if (product.hosted) {
                return true;
            }

            let fieldsValid = true;
            let activeFieldsCollection = registry.get('uid = ingenico-' + this.code + '-fields');
            for (let fieldComponent of activeFieldsCollection.elems()) {
                if (fieldComponent.field) {
                    paymentData.fieldData[fieldComponent.field.id] = fieldComponent.value();
                    if (!fieldComponent.validate().valid) {
                        fieldsValid = false;
                    }
                }
            }
            return fieldsValid;
        },

        getData: function () {
            let product = window.checkoutConfig.payment.ingenico.products[this.code];
            return {
                'method': this.item.method,
                'additional_data': {
                    'public_hash': this.getToken(),
                    'input': paymentData.getCurrentPayload(),
                    'product': product.id,
                    'tokenize': paymentData.tokenize().indexOf(product.id) !== -1,
                }
            };
        },

        placeOrder: function (data, event) {
            let parentMethod = this._super.bind(this);
            // paymentData.setCurrentPaymentProduct(this.product);
            // paymentData.setCurrentAccountOnFile(this.account);

            if (!this.validate()) {
                return false;
            }
            let product = window.checkoutConfig.payment.ingenico.products[this.code];
            if (product.hosted) {
                parentMethod(data, event);
            } else {
                this.createPayload().then(function () {
                    parentMethod(data, event);
                }, function (error) {
                    console.error('Could not create payload.', error);
                    alert('Payment error: ' + error);
                });
            }
        },

        /**
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },

        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.maskedCard;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details['expiry'];
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details['type'];
        }
    });
});
