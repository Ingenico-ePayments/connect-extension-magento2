/*browser:true*/
/*global define*/

define([],
    function () {
        'use strict';

        let config = {};

        /**
         * Initialize configuration with window.checkoutConfig object
         * @param configObject {object}
         */
        let init = function (configObject) {
            config = configObject;
        };

        /**
         * Get hostedCheckout index url
         * @returns {string}
         */
        let getHostedCheckoutUrl = function () {
            return config.hostedCheckoutPageUrl;
        };

        /**
         * Get hostedCheckout index url
         * @returns {string}
         */
        let getHostedCheckoutTitle = function () {
            return config.hostedCheckoutTitle;
        };

        /**
         * Get Url that redirects to the success page url
         * @returns {string}
         */
        let getInlineSuccessUrl = function () {
            return config.inlineSuccessUrl;
        };

        /**
         * Get current locale
         * @returns {string}
         */
        let getLocale = function () {
            return config.locale;
        };

        /**
         * Get image url of a loading spinner.
         * @return {string}
         */
        let getLoaderImage = function () {
            return config.loaderImage;
        };

        /**
         * Whether to use inline fields for payments, or the Hosted Checkout.
         * @return {boolean}
         */
        let useInlinePayments = function (paymentProduct) {
            if (paymentProduct === null || paymentProduct === false) {
                return false;
            }
            let paymentProductId = paymentProduct.paymentProductGroup !== undefined ?
                paymentProduct.paymentProductGroup : paymentProduct.id
            let inlinePaymentProducts = config.inlinePaymentProducts;
            if (!paymentProductId ||
                !inlinePaymentProducts ||
                !inlinePaymentProducts.includes(paymentProductId.toString())
            ) {
                return false;
            }
            return true;
        };

        /**
         * Whether to show any payment products or just Hosted Checkout only.
         * @return {boolean}
         */
        let useFullRedirect = function () {
            return Boolean(Number(config.useFullRedirect));
        };

        let isPaymentProductEnabled = function (paymentProduct) {
            let disabledPaymentProducts = config.disabledPaymentProducts;
            if (!disabledPaymentProducts) {
                return true;
            }
            let paymentProductId = paymentProduct.paymentProductGroup !== undefined ?
                paymentProduct.paymentProductGroup : paymentProduct.id
            if (!paymentProductId || disabledPaymentProducts.includes(paymentProductId.toString())) {
                return false;
            }
            return true;
        }

        let isPriceWithinPaymentProductPriceRange = function (paymentProduct, price, currency) {
            let priceRangedPaymentProducts = config.priceRangedPaymentProducts;
            if (!priceRangedPaymentProducts) {
                return true;
            }
            let paymentProductId = paymentProduct.paymentProductGroup !== undefined ?
                paymentProduct.paymentProductGroup : paymentProduct.id
            if (!paymentProductId) {
                return false;
            }
            let priceRanges = priceRangedPaymentProducts[paymentProductId.toString()];
            if (priceRanges !== undefined) {
                if (priceRanges[currency] !== undefined) {
                    let priceRange = priceRanges[currency];
                    if (priceRange['min'] !== undefined && priceRange['min'] > price) {
                        return false;
                    }
                    if (priceRange['max'] !== undefined && priceRange['max'] < price) {
                        return false;
                    }
                }
            }
            return true;
        }

        let isPaymentProductCountryRestricted = function (paymentProduct, countryCode) {
            let countryRestrictedPaymentProducts = config.countryRestrictedPaymentProducts;
            if (!countryRestrictedPaymentProducts) {
                return false;
            }
            let paymentProductId = paymentProduct.paymentProductGroup !== undefined ?
                paymentProduct.paymentProductGroup : paymentProduct.id
            if (!paymentProductId) {
                return true;
            }
            let paymentProductCountryRestrictions = countryRestrictedPaymentProducts[paymentProductId.toString()];
            if (paymentProductCountryRestrictions !== undefined) {
                return paymentProductCountryRestrictions.includes(countryCode);
            }
            return false;
        }

        /**
         * Returns if a customer is logged in
         * @returns {boolean}
         */
        let isCustomerLoggedIn = function () {
            return Boolean(Number(config.isCustomerLoggedIn));
        };

        /**
         * Whether card payment methods need to be grouped
         */
        let groupCardPaymentMethods = function () {
            return Boolean(Number(config.groupCardPaymentMethods));
        };

        /**
         * Get session ID created by Connect
         * @returns {any}
         */
        let getConnectSession = function () {
            return config.connectSession;
        }

        /**
         * Should frontend XHR requests be logged?
         * @returns {boolean}
         */
        let isLogFrontendRequests = function () {
            return Boolean(config.logFrontendRequests);
        }

        let inlinePaymentProducts = function () {
            return config.inlinePaymentProducts;
        }

        let disabledPaymentProducts = function () {
            return config.disabledPaymentProducts;
        }

        let priceRangedPaymentProducts = function () {
            return config.priceRangedPaymentProducts;
        }

        let countryRestrictedPaymentProducts = function () {
            return config.countryRestrictedPaymentProducts;
        }

        let saveForLaterVisible = function () {
            return config.saveForLaterVisible;
        }

        return {
            init: init,
            getHostedCheckoutUrl: getHostedCheckoutUrl,
            getHostedCheckoutTitle: getHostedCheckoutTitle,
            getInlineSuccessUrl: getInlineSuccessUrl,
            getLocale: getLocale,
            useInlinePayments: useInlinePayments,
            useFullRedirect: useFullRedirect,
            isPaymentProductEnabled: isPaymentProductEnabled,
            isPriceWithinPaymentProductPriceRange: isPriceWithinPaymentProductPriceRange,
            isPaymentProductCountryRestricted: isPaymentProductCountryRestricted,
            getLoaderImage: getLoaderImage,
            isCustomerLoggedIn: isCustomerLoggedIn,
            groupCardPaymentMethods: groupCardPaymentMethods,
            connectSession: getConnectSession,
            isLogFrontendRequests: isLogFrontendRequests,
            inlinePaymentProducts: inlinePaymentProducts,
            disabledPaymentProducts: disabledPaymentProducts,
            priceRangedPaymentProducts: priceRangedPaymentProducts,
            countryRestrictedPaymentProducts: countryRestrictedPaymentProducts,
            saveForLaterVisible: saveForLaterVisible
        };
    }
);
