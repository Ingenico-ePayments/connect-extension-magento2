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
        try {
            let sdkClient = client.initialize();
            let payload = {
                totalAmount: Math.round(parseFloat(quote.getTotals()()['base_grand_total']).toFixed(2) * 100),
                countryCode: quote.billingAddress().countryId,
                currency: quote.getTotals()()['base_currency_code'],
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
});
