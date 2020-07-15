/*browser:true*/
/*global define*/

define([
    'Ingenico_Connect/js/model/payment/payment-data',
    'Ingenico_Connect/js/model/payment/products',
    'Ingenico_Connect/js/action/get-iin-details',
    'Ingenico_Connect/js/action/get-payment-product'
], function (paymentData, productList, getIinDetails, getPaymentProduct) {
    'use strict';

    return {
        updateCardType: function (partialCardNumber) {
            getIinDetails(partialCardNumber).then(function (response) {
                if (response.paymentProductId) {
                    getPaymentProduct(response.paymentProductId).then(function (productResponse) {
                        paymentData.setCurrentCardPaymentProduct(productResponse);
                    }, function() {
                        paymentData.setCurrentCardPaymentProduct(null);
                    });

                    return;
                }

                paymentData.setCurrentCardPaymentProduct(null);
            }, function() {
                paymentData.setCurrentCardPaymentProduct(null);
            });
        },

        clearCardType: function() {
            paymentData.setCurrentCardPaymentProduct(null);
        }
    }
});
