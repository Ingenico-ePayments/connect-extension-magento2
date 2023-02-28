define([
    'jquery',
    'Worldline_Connect/js/model/payment/config'
], function ($, config) {
    "use strict";

    function uuidv4() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = parseFloat('0.' + Math.random().toString().replace('0.', '') + new Date().getTime()) * 16 | 0,
                v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    return {
        logRequest: function (type, jsonData) {
            let requestId = uuidv4();

            if (!config.isLogFrontendRequests()) {
                return requestId;
            }

            $.ajax(
                '/rest/default/V1/worldline-connect/log-request',
                {
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        type: type,
                        jsonData: JSON.stringify(jsonData),
                        requestId: requestId
                    })
                }
            );

            return requestId;
        },

        logResponse: function (requestId, jsonData) {
            if (!config.isLogFrontendRequests()) {
                return;
            }

            $.ajax(
                '/rest/default/V1/worldline-connect/log-response',
                {
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        jsonData: JSON.stringify(jsonData),
                        requestId: requestId
                    })
                }
            );
        }
    };
});
