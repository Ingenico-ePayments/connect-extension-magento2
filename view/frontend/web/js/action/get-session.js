define([
    'ko',
    'Ingenico_Connect/js/model/payment/config'
], function (ko, config) {
    'use strict';

    return function () {
        let sessionData = config.connectSession();
        if (sessionData.error) {
            let message = 'Could not load Ingenico session data: ' + sessionData.error;
            console.warn(message);
            throw message;
        }
        return sessionData.data;
    }
});
