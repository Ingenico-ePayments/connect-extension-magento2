define([
    'Ingenico_Connect/js/view/payment/component/field',
    'Ingenico_Connect/js/model/payment/payment-data',
    'Ingenico_Connect/js/model/payment-method/card',
    'ko',
    'uiRegistry'
], function (Field, paymentData, cardPayment, ko, registry) {
    'use strict';

    return Field.extend({

        defaults: {
            lastValue: '',
            logo: '',
            logoDescription: '',
        },

        initialize: function () {
            this._super();

            paymentData.currentCardPaymentProduct.subscribe(this.updateProductInfo.bind(this));

            this.logo = ko.observable('');
            this.logoDescription = ko.observable('');

            return this;
        },

        getTemplateForType: function (type) {
            if (this.field.id === 'cardNumber') {
                return 'Ingenico_Connect/payment/product/card/field/cardnumber';
            }

            let templates = {
                text: 'Ingenico_Connect/payment/product/field/input',
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
            registry.get('cardAllowsTokenization')(currentCardPaymentProduct === null ? false : currentCardPaymentProduct.allowsTokenization === true);
        }
    });
});
