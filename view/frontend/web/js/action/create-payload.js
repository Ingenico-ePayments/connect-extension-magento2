define([
    'Netresearch_Epayments/js/model/client'
], function (client) {
    'use strict';
    /**
     * @param {object} data - available keys: 'cardNumber', 'expiryDate', 'cvv', 'phoneNumber', 'paymentProduct',
     *                        'accountOnFile'.
     * @return {string|false}
     */
    return function (data) {
        let sdkClient = client.initialize();
        let encryptor = sdkClient.getEncryptor();
        let request = sdkClient.getPaymentRequest();

        if (data['paymentProduct']) {
            request.setPaymentProduct(data['paymentProduct']);
            delete data['paymentProduct'];
        }
        if (data['accountOnFile']) {
            request.setAccountOnFile(data['accountOnFile']);
            delete data['accountOnFile'];
        }
        /**
         * If there is no special payload data, don't create a payload.
         */
        if (Object.keys(data).length === 0) {
            return Promise.resolve(false);
        }
        for (let key in data) {
            request.setValue(key, data[key]);
        }
        if (!request.isValid()) {
            return Promise.reject('Error creating payment request, data not valid.');
        }

        return encryptor.encrypt(request);
    }
});
