/*browser:true*/
/*global define*/
define([
    'Worldline_Connect/js/action/get-payment-products',
    'Worldline_Connect/js/action/get-payment-product',
    'Worldline_Connect/js/action/get-card-payment-group',
    'Worldline_Connect/js/model/payment/config',
    'ko',
    'Magento_Checkout/js/model/quote'
], function (fetchPaymentProducts, fetchPaymentProduct, fetchCardPaymentGroup, config, ko, quote) {
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
                if (config.groupCardPaymentMethods()) {
                    const cardPaymentMethodIds = [];
                    paymentProductsResponse.basicPaymentProducts.forEach(function (paymentProduct) {
                        if (!paymentProduct.paymentProductGroup || paymentProduct.paymentProductGroup !== 'cards') {
                            return;
                        }

                        cardPaymentMethodIds.push(paymentProduct.id);
                    });

                    cardPaymentMethodIds.forEach(function (id) {
                        if (paymentProductsResponse.basicPaymentProductById[id].accountsOnFile.length === 0) {
                            delete paymentProductsResponse.basicPaymentProductById[id];
                        }
                    });

                    paymentProductsResponse.basicPaymentProducts = paymentProductsResponse.basicPaymentProducts.filter(
                        function (paymentProduct) {
                            return cardPaymentMethodIds.indexOf(paymentProduct.id) === -1;
                        }
                    );
                }

                cardGroupPaymentMethod(responses[1]);
                productsResponse(paymentProductsResponse);
                let cardsPaymentProduct = {id:'cards'};
                if (config.isPaymentProductEnabled(cardsPaymentProduct) &&
                    config.isPriceWithinPaymentProductPriceRange(
                        cardsPaymentProduct,
                        Math.round(parseFloat(quote.getTotals()()['base_grand_total']).toFixed(2) * 100) / 100,
                        quote.getTotals()()['base_currency_code']
                    ) &&
                    !config.isPaymentProductCountryRestricted(cardsPaymentProduct, quote.billingAddress().countryId)
                ) {
                    accountsOnFile(paymentProductsResponse.accountsOnFile);
                }
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
