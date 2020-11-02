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
            let payload = {
                totalAmount: Math.round(parseFloat(quote.getTotals()()['base_grand_total']) * 100).toFixed(0),
                countryCode: quote.billingAddress().countryId,
                currency: quote.getTotals()()['base_currency_code'],
                isRecurring: false,
                locale: localeResolver.getBaseLocale(config.getLocale())
            };

            requestId = logger.logRequest('getPaymentProduct', payload);
            let response = sdkClient.getBasicPaymentProducts(payload);
            response.then((result) => {
                logger.logResponse(requestId, result);
            })
            return response;
        } catch (error) {
            logger.logResponse(requestId, error);
            return Promise.reject(error);
        }
    }
});
