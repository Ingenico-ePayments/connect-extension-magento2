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
        let useInlinePayments = function () {
            return Boolean(Number(config.useInlinePayments));
        };

        /**
         * Whether to show any payment products or just Hosted Checkout only.
         * @return {boolean}
         */
        let useFullRedirect = function () {
            return Boolean(Number(config.useFullRedirect));
        };

        return {
            init: init,
            getHostedCheckoutUrl: getHostedCheckoutUrl,
            getInlineSuccessUrl: getInlineSuccessUrl,
            getLocale: getLocale,
            useInlinePayments: useInlinePayments,
            useFullRedirect: useFullRedirect,
            getLoaderImage: getLoaderImage,
        };
    }
);
