/*browser:true*/
/*global define*/

define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Ingenico_Connect/js/model/client',
], function (ko, quote, client) {
    'use strict';

    return function (partialCardNumber) {
        try {
            let sdkClient = client.initialize();

            return sdkClient.getIinDetails(partialCardNumber, null);
        } catch (error) {
            return Promise.reject(error);
        }
    }
});
