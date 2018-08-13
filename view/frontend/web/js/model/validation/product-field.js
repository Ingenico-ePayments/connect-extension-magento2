define([
    'Magento_Ui/js/lib/validation/validator',
], function (validator) {
    'use strict';

    return {

        /**
         * Register product field validator with Magento
         */
        register: function () {
            validator.addRule(
                'ingenico-field',
                this.validate,
                'Please check your input.'
            );
        },
        /**
         * Validate a product field value via its PaymentProductField object
         *
         * @param {string} value
         * @param {array} params
         * @param {array} additionalParams
         * @return {boolean}
         */
        validate: function (value, params, additionalParams) {
            let isValid = true;

            if (value !== '') {
                /** @type {PaymentProductField} field */
                let field = params['productField'];
                isValid = field.isValid(value);
            }

            return isValid;
        }
    }
});
