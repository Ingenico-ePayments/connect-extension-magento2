/*browser:true*/
/*global define*/

define([
    'Ingenico_Connect/js/action/get-payment-products',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/action/get-card-payment-group',
    'Ingenico_Connect/js/model/payment/config',
    'ko'
], function (fetchPaymentProducts, fetchPaymentProduct, fetchCardPaymentGroup, config, ko) {
    'use strict';

    let basicPaymentProducts = ko.observableArray([]).extend({
            rateLimit: {method: "notifyWhenChangesStop", timeout: 20}
        }),
        accountsOnFile = ko.observableArray([]).extend({
            rateLimit: {method: "notifyWhenChangesStop", timeout: 20}
        }),
        cardGroupPaymentMethod = ko.observable(),
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
        cardGroupPaymentMethod: cardGroupPaymentMethod,

        /**
         * @param {HTMLElement} messageContainer
         */
        reload: function (messageContainer) {
            isLoading(true);
            Promise.all([fetchPaymentProducts(), fetchCardPaymentGroup()]).then(responses => {
                const paymentProductsResponse = responses[0];

                if (!config.useInlinePayments()) {
                    paymentProductsResponse.basicPaymentProducts = paymentProductsResponse.basicPaymentProducts.filter(disallowProductByIdFilter);
                    disallowedProductIds.forEach(function (id) {
                        delete paymentProductsResponse.basicPaymentProductById[id];
                    });
                }

                if (config.groupCardPaymentMethods()) {
                    const cardPaymentMethodIds = [];
                    paymentProductsResponse.basicPaymentProducts.forEach(function (paymentProduct) {
                        if (!paymentProduct.paymentProductGroup || paymentProduct.paymentProductGroup !== 'cards') {
                            return;
                        }

                        cardPaymentMethodIds.push(paymentProduct.id);
                    });

                    cardPaymentMethodIds.forEach(function (id) {
                        delete paymentProductsResponse.basicPaymentProductById[id];
                    });

                    paymentProductsResponse.basicPaymentProducts = paymentProductsResponse.basicPaymentProducts.filter(
                        function (paymentProduct) {
                            return cardPaymentMethodIds.indexOf(paymentProduct.id) === -1;
                        }
                    );
                }

                cardGroupPaymentMethod(responses[1]);
                productsResponse(paymentProductsResponse);
                accountsOnFile(paymentProductsResponse.accountsOnFile);
                basicPaymentProducts(paymentProductsResponse.basicPaymentProducts.sort(sortFunction));

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
