/*browser:true*/
/*global define*/

define([
    'Magento_Checkout/js/model/quote',
    'Ingenico_Connect/js/model/client',
    'Ingenico_Connect/js/model/payment/config'
], function (quote, client, config) {
    'use strict';

    return function () {
        try {
            let sdkClient = client.initialize();
            let payload = {
                totalAmount: (parseFloat(quote.getTotals()()['base_grand_total']) * 100).toFixed(0),
                countryCode: quote.billingAddress().countryId,
                currency: quote.getTotals()()['base_currency_code'],
                isRecurring: false,
                locale: config.getLocale()
            };

            return sdkClient.getBasicPaymentProducts(payload);
        } catch (error) {
            return Promise.reject(error);
        }
    }
});
