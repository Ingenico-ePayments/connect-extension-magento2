define([
    'ko',
    'Magento_Customer/js/customer-data'
], function (ko, customerData) {
    'use strict';

    var cacheKey = 'connect_session';
    var sessionData = customerData.get(cacheKey);

    return function () {
        if (sessionData().error) {
            var message = 'Could not load Ingenico session data: ' + sessionData().error;
            console.warn(message);
            throw message;
        }
        return sessionData().data;
    }
});
