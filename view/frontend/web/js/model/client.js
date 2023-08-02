/*browser:true*/
/*global define*/

define([
    'Worldline_Connect/js/action/get-session',
    'connectsdk.core'
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
                sdkClient = new sdk.Session(getSessionAction());
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
