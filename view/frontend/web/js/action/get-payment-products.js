/*browser:true*/
/*global define*/

define([
    'Magento_Checkout/js/model/quote',
    'Ingenico_Connect/js/model/client',
    'Ingenico_Connect/js/model/payment/config',
    'Ingenico_Connect/js/model/locale-resolver'
], function (quote, client, config, localeResolver) {
    'use strict';

    return function () {
        try {
            let sdkClient = client.initialize();
            let payload = {
                totalAmount: (parseFloat(quote.getTotals()()['base_grand_total']) * 100).toFixed(0),
                countryCode: quote.billingAddress().countryId,
                currency: quote.getTotals()()['base_currency_code'],
                isRecurring: false,
                locale: localeResolver.getBaseLocale(config.getLocale())
            };

            return sdkClient.getBasicPaymentProducts(payload);
        } catch (error) {
            return Promise.reject(error);
        }
    }
});
