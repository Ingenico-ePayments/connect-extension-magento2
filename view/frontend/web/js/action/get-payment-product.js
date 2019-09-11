/*browser:true*/
/*global define*/

define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Ingenico_Connect/js/model/client',
    'Ingenico_Connect/js/model/payment/config'
], function (ko, quote, client, config) {
    'use strict';

    return function (productId) {
        try {
            let sdkClient = client.initialize();
            let payload = {
                totalAmount: parseFloat(quote.getTotals()()['base_grand_total']).toFixed(2) * 100,
                countryCode: quote.billingAddress().countryId,
                currency: quote.getTotals()()['base_currency_code'],
                isRecurring: false,
                locale: config.getLocale()
            };

            return sdkClient.getPaymentProduct(productId, payload);
        } catch (error) {
            return Promise.reject(error);
        }
    }
});
