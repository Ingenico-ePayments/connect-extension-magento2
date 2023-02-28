/*browser:true*/
/*global define*/

define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Worldline_Connect/js/model/client',
    'Worldline_Connect/js/action/logger',
    'Worldline_Connect/js/model/payment/config'
], function (ko, quote, client, logger, config) {
    'use strict';

    return function (partialCardNumber) {
        let requestId;
        try {
            let sdkClient = client.initialize();
            requestId = logger.logRequest('getIinDetails', {});
            let response = sdkClient.getIinDetails(partialCardNumber, null);
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
