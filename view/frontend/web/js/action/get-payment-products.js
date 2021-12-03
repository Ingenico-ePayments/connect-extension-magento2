/*browser:true*/
/*global define*/

define([
    'Magento_Checkout/js/model/quote',
    'Ingenico_Connect/js/model/client',
    'Ingenico_Connect/js/model/payment/config',
    'Ingenico_Connect/js/model/locale-resolver',
    'Ingenico_Connect/js/action/logger'
], function (quote, client, config, localeResolver, logger) {
    'use strict';

    return function () {
        let requestId;
        try {
            let sdkClient = client.initialize();
            let totalAmount = Math.round(parseFloat(quote.getTotals()()['base_grand_total']) * 100).toFixed(0)
            let countryCode = quote.billingAddress().countryId;
            let currencyCode = quote.getTotals()()['base_currency_code'];
            let payload = {
                totalAmount: totalAmount,
                countryCode: countryCode,
                currency: currencyCode,
                isRecurring: false,
                locale: localeResolver.getBaseLocale(config.getLocale())
            };

            requestId = logger.logRequest('getPaymentProduct', payload);
            let response = sdkClient.getBasicPaymentProducts(payload);
            response.then((result) => {
                result.basicPaymentProducts = result.basicPaymentProducts.filter(
                    function (paymentProduct) {
                        return config.isPaymentProductEnabled(paymentProduct) &&
                            config.isPriceWithinPaymentProductPriceRange(paymentProduct, totalAmount / 100, currencyCode) &&
                            !config.isPaymentProductCountryRestricted(paymentProduct, countryCode)
                    }
                );
                logger.logResponse(requestId, result);
            })
            return response;
        } catch (error) {
            logger.logResponse(requestId, error);
            return Promise.reject(error);
        }
    }
});
