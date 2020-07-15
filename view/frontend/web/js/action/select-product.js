define([
    'Ingenico_Connect/js/model/payment/payment-data',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/model/payment/config',
    'uiRegistry',
], function (paymentData, fetchProduct, config, registry) {
    'use strict';

    let fieldsComponentName = 'Ingenico_Connect/js/view/payment/component/collection/fields';

    let activeFieldsCollection = {};

    let updatePaymentProduct = function () {
        if (activeFieldsCollection.product.id === 'cards') {
            paymentData.setCurrentPaymentProduct(activeFieldsCollection.product);
            return;
        }

        fetchProduct(activeFieldsCollection.product.id).then(function (fullProduct) {
            paymentData.setCurrentPaymentProduct(fullProduct);
        });
    };

    let updatePaymentData = function () {
        paymentData.fieldData = {};

        let fieldComponents = activeFieldsCollection.elems();
        for (let fieldComponent of fieldComponents) {
            if (!fieldComponent.field) {
                continue;
            }

            paymentData.fieldData[fieldComponent.field.id] = fieldComponent.value();
        }

        activeFieldsCollection.elems.subscribe(function (fieldComponents) {
            for (let fieldComponent of fieldComponents) {
                if (fieldComponent.field) {
                    fieldComponent.value.subscribe(function (value) {
                        paymentData.fieldData[fieldComponent.field.id] = value;
                    });
                }
            }
        });

        if (activeFieldsCollection.account) {
            paymentData.setCurrentAccountOnFile(activeFieldsCollection.account);
        } else {
            paymentData.setCurrentAccountOnFile(false)
        }
    };

    let toggleActiveFieldsCollection = function () {
        let fieldsCollections = registry.filter(
            'component = ' + fieldsComponentName
        );
        for (let fieldsCollection of fieldsCollections) {
            fieldsCollection.fieldsVisiblity(false);
        }

        activeFieldsCollection.initFields();
    };

    return function (productIdentifier) {
        activeFieldsCollection = registry.get(
            'component = ' + fieldsComponentName + ',' +
            'uid = ' + productIdentifier
        );
        updatePaymentProduct();
        if (config.useInlinePayments()) {
            updatePaymentData();
            toggleActiveFieldsCollection();
        }
    };
});
