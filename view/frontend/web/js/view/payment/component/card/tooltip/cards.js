define([
    'Magento_Ui/js/form/element/abstract',
    'Ingenico_Connect/js/model/payment/payment-data',
    'Ingenico_Connect/js/model/payment-method/card',
    'Ingenico_Connect/js/action/get-payment-products',
    'ko'
], function (Abstract, paymentData, cardPayment, getPaymentProducts, ko) {
    'use strict';

    let extractCardPaymentProducts = function (paymentProducts) {
        let cardPaymentProducts = [];
        paymentProducts.forEach(function(paymentProduct) {
            if (paymentProduct.paymentMethod === 'card') {
                cardPaymentProducts.push(paymentProduct);
            }
        })
        return cardPaymentProducts;
    }

    let sortFunction = function (a, b) {
        return a.displayHints.displayOrder - b.displayHints.displayOrder
    };

    return Abstract.extend({

        defaults: {
            creditCardLogos: '',
            template: 'Ingenico_Connect/payment/product/card/tooltip/cards',
        },

        initialize: function () {
            this._super();
            this.creditCardLogos = ko.observableArray([]);

            this.fetchCreditCardLogos();

            return this;
        },

        getTemplateForType: function (type) {
            return 'Ingenico_Connect/payment/product/card/tooltip/cards';
        },

        fetchCreditCardLogos: function () {
            getPaymentProducts().then(response => {
                let creditCardLogos = [];
                const paymentProducts = extractCardPaymentProducts(response.basicPaymentProducts.sort(sortFunction));
                paymentProducts.forEach(function(paymentProduct) {
                    creditCardLogos.push(paymentProduct.displayHints.logo);
                });
                this.creditCardLogos(creditCardLogos);
            })
        }
    });
});
