/*browser:true*/
/*global define*/

define([
    'Netresearch_Epayments/js/action/get-session',
    'connect-sdk'
], function (getSessionAction, sdk) {
    'use strict';

    var sdkClient = null;

    return {
        /**
         * Initialize the connectsdk session object or return it, if it was already initialized.
         * Draw necessary session configuration from sectionData.
         *
         * @returns {connectsdk.Session}
         */
        initialize: function () {
            if (sdkClient === null) {
                var sessionData = getSessionAction();
                sessionData = {
                    apiBaseUrl: sessionData.clientApiUrl + "/v1",
                    assetsBaseUrl: sessionData.assetUrl,
                    clientSessionID: sessionData.clientSessionId,
                    customerId: sessionData.customerId
                };
                sdkClient = new sdk.Session(sessionData);
            }
            return sdkClient;
        },

        /**
         * Reset the already initialized client and reinitialize it.
         *
         * @returns {connectsdk.Session}
         */
        reset: function () {
            sdkClient = null;
            return this.initialize();
        }
    };
});
