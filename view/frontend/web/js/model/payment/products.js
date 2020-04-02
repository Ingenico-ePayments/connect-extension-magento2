/*browser:true*/
/*global define*/

define([
    'Ingenico_Connect/js/action/get-payment-products',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/model/payment/config',
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

    // These product ID's are not allowed in RPP:
    let disallowedProductIds = [302, 320, 770, 730, 705, 201];
    let disallowProductByIdFilter = function (paymentProduct) {
        return disallowedProductIds.indexOf(paymentProduct.id) === -1;
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

                // Remove payment methods that cannot be used for the RPP:
                if (!config.useInlinePayments()) {
                    response.basicPaymentProducts = response.basicPaymentProducts.filter(disallowProductByIdFilter);
                    disallowedProductIds.forEach(function (id) {
                        delete response.basicPaymentProductById[id];
                    });
                }

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
