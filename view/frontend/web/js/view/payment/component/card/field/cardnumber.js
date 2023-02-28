define([
    'Worldline_Connect/js/view/payment/component/field',
    'Worldline_Connect/js/model/payment/payment-data',
    'Worldline_Connect/js/model/payment-method/card',
    'Worldline_Connect/js/action/get-payment-products',
    'ko',
    'uiRegistry'
], function (Field, paymentData, cardPayment, getPaymentProducts, ko, registry) {
    'use strict';

    let extractCardPaymentProducts = function (paymentProducts) {
        let cardPaymentProducts = [];
        paymentProducts.forEach(function (paymentProduct) {
            if (paymentProduct.paymentMethod === 'card') {
                cardPaymentProducts.push(paymentProduct);
            }
        })
        return cardPaymentProducts;
    }

    let sortFunction = function (a, b) {
        return a.displayHints.displayOrder - b.displayHints.displayOrder
    };

    return Field.extend({

        defaults: {
            lastValue: '',
            logo: '',
            logoDescription: '',
            creditCardLogos: '',
        },

        initialize: function () {
            this._super();

            paymentData.currentCardPaymentProduct.subscribe(this.updateProductInfo.bind(this));

            this.logo = ko.observable('');
            this.logoDescription = ko.observable('');
            this.creditCardLogos = ko.observableArray([]);

            this.fetchCreditCardLogos();

            return this;
        },

        getTemplateForType: function (type) {
            if (this.field.id === 'cardNumber') {
                return 'Worldline_Connect/payment/product/card/field/cardnumber';
            }

            let templates = {
                text: 'Worldline_Connect/payment/product/field/input',
                checkbox: 'ui/form/element/single-checkbox',
                list: 'ui/form/element/select',
            };
            if (templates[type]) {
                return templates[type];
            }

            return false;
        },

        onUpdate: function (value) {
            if (value.length >= 6) {
                const trimmedValue = value.replace(' ', '').substring(0, 6);

                if (trimmedValue.length < 6) {
                    cardPayment.clearCardType();
                    this.lastValue = null;
                } else {
                    if (trimmedValue !== this.lastValue) {
                        cardPayment.updateCardType(value);
                        this.lastValue = trimmedValue;
                    }
                }
            }

            /** Apply mask */
            if (this.value()) {
                let newValue;
                if (this.account && this.account.attributeByKey[this.field.id]) {
                    newValue = this.field.applyWildcardMask(this.value()).formattedValue;
                } else {
                    newValue = this.field.applyMask(this.value()).formattedValue;
                }
                this.value(newValue);
            }

            /** Rate-limit validation */
            if (!this.debouncedSuper) {
                this.debouncedSuper = _.debounce(
                    this._super.bind(this),
                    1000
                )
            }
            this.debouncedSuper(value);
        },

        updateProductInfo: function () {
            const currentCardPaymentProduct = paymentData.getCurrentCardPaymentProduct();
            this.logo(currentCardPaymentProduct === null ? '' : currentCardPaymentProduct.displayHints.logo);
            this.logoDescription(currentCardPaymentProduct === null ? '' : currentCardPaymentProduct.displayHints.label);
            const cardAllowsTokenization = registry.get('cardAllowsTokenization');
            if (cardAllowsTokenization) {
                cardAllowsTokenization(currentCardPaymentProduct === null ? false : currentCardPaymentProduct.allowsTokenization === true);
            }
        },

        fetchCreditCardLogos: function () {
            getPaymentProducts().then(response => {
                let creditCardLogos = [];
                const paymentProducts = extractCardPaymentProducts(response.basicPaymentProducts.sort(sortFunction));
                paymentProducts.forEach(function (paymentProduct) {
                    creditCardLogos.push(paymentProduct.displayHints.logo);
                });
                this.creditCardLogos(creditCardLogos);
            })
        }
    });
});
