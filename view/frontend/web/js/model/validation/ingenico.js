define([
    'Ingenico_Connect/js/model/payment/payment-data',
    'Ingenico_Connect/js/model/payment/config',
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
            if (product && product.id === 'cards') {
                const cardPaymentProduct = paymentData.getCurrentCardPaymentProduct();
                if (cardPaymentProduct) {
                    product = cardPaymentProduct;
                }
            }

            if (!product) {
                alert('Please select a payment product');
                return false;
            }

            let fieldsValid = true;
            let activeFieldsCollection = registry.get(
                'component = Ingenico_Connect/js/view/payment/component/collection/fields,' +
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
