define([
    'ko',
    'Worldline_Connect/js/model/payment/config'
], function (ko, config) {
    'use strict';

    return function () {
        let sessionData = config.connectSession();
        if (sessionData.error) {
            let message = 'Could not load Worldline session data: ' + sessionData.error;
            console.warn(message);
            throw message;
        }
        return sessionData.data;
    }
});
