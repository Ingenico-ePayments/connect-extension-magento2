define([
    'Netresearch_Epayments/js/model/payment/payment-data',
    'Netresearch_Epayments/js/model/payment/config',
    'uiRegistry'
], function (paymentData, config, registry) {
    'use strict';

    return {
        /**
         * Validate product selection and all currently visible product field components
         *
         * @return {boolean}
         */
        validate: function () {
            if (config.useFullRedirect()) {
                return true;
            }

            let product = paymentData.getCurrentPaymentProduct();

            if (!product) {
                alert('Please select a payment product');
                return false;
            }

            let fieldsValid = true;
            let activeFieldsCollection = registry.get(
                'component = Netresearch_Epayments/js/view/payment/component/collection/fields,' +
                'uid = ' + paymentData.getCurrentProductIdentifier()
            );

            for (let fieldComponent of activeFieldsCollection.elems()) {
                if (fieldComponent.field) {
                    if (!fieldComponent.validate().valid) {
                        fieldsValid = false;
                    }
                }
            }

            return fieldsValid;
        }
    };
});
