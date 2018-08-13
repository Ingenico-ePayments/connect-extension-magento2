/*browser:true*/
/*global define*/

define([
    'Netresearch_Epayments/js/action/get-payment-products',
    'Netresearch_Epayments/js/action/get-payment-product',
    'Netresearch_Epayments/js/model/payment/config',
    'ko'
], function (fetchPaymentProducts, fetchPaymentProduct, config, ko) {
    'use strict';

    let basicPaymentProducts = ko.observableArray([]).extend({
            rateLimit: {method: "notifyWhenChangesStop", timeout: 20}
        }),
        accountsOnFile = ko.observableArray([]).extend({
            rateLimit: {method: "notifyWhenChangesStop", timeout: 20}
        }),
        productsResponse = ko.observable();

    let isLoading = ko.observable(false);

    let sortFunction = function (a, b) {
        return a.displayHints.sortOrder - b.displayHints.sortOrder
    };

    return {

        isLoading: isLoading,
        basicPaymentProducts: basicPaymentProducts,
        accountsOnFile: accountsOnFile,
        productsResponse: productsResponse,

        /**
         * @param {HTMLElement} messageContainer
         */
        reload: function (messageContainer) {
            isLoading(true);
            fetchPaymentProducts().then(function (response) {

                // transfer response to locally stored variables
                productsResponse(response);
                accountsOnFile(response.accountsOnFile);
                basicPaymentProducts(response.basicPaymentProducts.sort(sortFunction));

                isLoading(false);
            }, function () {
                isLoading(false);
                messageContainer.addErrorMessage({
                    "message": "Could not fetch payment options from Ingenico Connect API. Please select another payment method."
                });
            });
        },

        /**
         * Return SimplePaymentProduct from a previous fetchPaymentProducts request
         * @param {int} productId
         */
        getById: function (productId) {
            return this.productsResponse().basicPaymentProductById[productId];
        }
    };
});
