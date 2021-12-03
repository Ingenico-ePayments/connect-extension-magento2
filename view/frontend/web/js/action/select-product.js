define([
    'Ingenico_Connect/js/model/payment/payment-data',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/model/payment/config',
    'uiRegistry',
], function (paymentData, fetchProduct, config, registry) {
    'use strict';

    let fieldsComponentName = 'Ingenico_Connect/js/view/payment/component/collection/fields';
    const tooltipsComponentName = 'Ingenico_Connect/js/view/payment/component/collection/tooltips';

    let activeFieldsCollection = {};
    let activeTooltipsCollection = {};

    let updatePaymentProduct = async function () {
        if (activeFieldsCollection.product.id === 'cards') {
            paymentData.setCurrentPaymentProduct(activeFieldsCollection.product);
            return;
        }

        await fetchProduct(activeFieldsCollection.product.id).then(function (fullProduct) {
            paymentData.setCurrentPaymentProduct(fullProduct);
            hideActiveFieldsCollection();
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

    let hideActiveFieldsCollection = function () {
        let fieldsCollections = registry.filter(
            'component = ' + fieldsComponentName
        );
        for (let fieldsCollection of fieldsCollections) {
            fieldsCollection.fieldsVisiblity(false);
        }
    }

    let toggleActiveFieldsCollection = function () {
        hideActiveFieldsCollection();
        if (activeFieldsCollection) {
            activeFieldsCollection.initFields();
        }
    };

    let toggleActiveTooltipsCollection = function () {
        let tooltipsCollections = registry.filter(
            'component = ' + tooltipsComponentName
        );
        for (let tooltipsCollection of tooltipsCollections) {
            tooltipsCollection.fieldsVisiblity(false);
        }
        if (activeTooltipsCollection) {
            activeTooltipsCollection.initTooltips();
        }
    };

    return async function (productIdentifier) {
        activeFieldsCollection = registry.get(
            'component = ' + fieldsComponentName + ',' +
            'uid = ' + productIdentifier
        );
        await updatePaymentProduct();
        if (config.useInlinePayments(paymentData.getCurrentPaymentProduct())) {
            updatePaymentData();
            toggleActiveFieldsCollection();
        } else {
            activeTooltipsCollection = registry.get(
                'component = ' + tooltipsComponentName + ',' +
                'uid = ' + productIdentifier
            );
            toggleActiveTooltipsCollection();
        }
    };
});
