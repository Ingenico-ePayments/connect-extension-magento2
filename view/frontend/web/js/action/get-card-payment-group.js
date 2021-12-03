/*browser:true*/
/*global define*/

define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Ingenico_Connect/js/model/client',
    'Ingenico_Connect/js/model/payment/config',
    'Ingenico_Connect/js/model/locale-resolver',
    'Ingenico_Connect/js/action/logger'
], function (ko, quote, client, config, localeResolver, logger) {
    'use strict';

    return function () {
        let requestId;
        let totalAmount = Math.round(parseFloat(quote.getTotals()()['base_grand_total']).toFixed(2) * 100);
        let currencyCode = quote.getTotals()()['base_currency_code'];
        let countryCode = quote.billingAddress().countryId;
        let cardsPaymentProduct = {id:'cards'};
        if (config.isPaymentProductEnabled(cardsPaymentProduct) &&
            config.isPriceWithinPaymentProductPriceRange(cardsPaymentProduct, totalAmount / 100, currencyCode) &&
            !config.isPaymentProductCountryRestricted(cardsPaymentProduct, countryCode)
        ) {
            try {
                let sdkClient = client.initialize();
                let payload = {
                    totalAmount: totalAmount,
                    countryCode: countryCode,
                    currency: currencyCode,
                    isRecurring: false,
                    locale: localeResolver.getBaseLocale(config.getLocale())
                };
                requestId = logger.logRequest('getPaymentProductGroup', payload);
                let response = sdkClient.getPaymentProductGroup('cards', payload);
                response.then((result) => {
                    logger.logResponse(requestId, result);
                })
                return response;
            } catch (error) {
                logger.logResponse(requestId, error);
                return Promise.reject(error);
            }
        }
        return null
    }
});
