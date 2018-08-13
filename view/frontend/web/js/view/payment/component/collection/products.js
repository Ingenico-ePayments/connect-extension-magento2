define([
    'Magento_Ui/js/form/components/group',
    'Netresearch_Epayments/js/model/payment/payment-data',
    'uiLayout',
], function (Group, paymentData, layout) {
    'use strict';

    let generateProductComponent = function (identifier, description, product, account) {
        return {
            parent: this.name,
            name: identifier,
            additionalClasses: identifier,
            component: 'Netresearch_Epayments/js/view/payment/component/product',
            description: description,
            checked: paymentData.currentProductIdentifier,
            product: product,
            account: account,
        };
    };

    let generateProductFields = function (identifier, product, account) {
        return {
            parent: this.name,
            name: identifier + '-fields',
            component: 'Netresearch_Epayments/js/view/payment/component/collection/fields',
            template: 'Netresearch_Epayments/payment/product/field-collection',
            uid: identifier,
            product: product,
            account: account,
        };
    };

    return Group.extend({

        defaults: {
            template: 'Netresearch_Epayments/payment/product/group',
            isTokenGroup: false,
        },

        initialize: function () {
            this._super();
            this.initChildren();

            return this;
        },

        initChildren: function () {
            let layouts = [];

            for (let product of this.products) {
                let account;
                let identifier = 'product-' + product.id;
                let description = product.displayHints.label;

                if (this.isTokenGroup) {
                    for (let account of product.accountsOnFile) {
                        identifier = 'token-' + account.id;
                        description = account.getMaskedValueByAttributeKey('alias').formattedValue;
                        layouts.push(generateProductComponent.call(this, identifier, description, product, account));
                        layouts.push(generateProductFields.call(this, identifier, product, account));
                    }
                    continue;
                }

                layouts.push(generateProductComponent.call(this, identifier, description, product, account));
                layouts.push(generateProductFields.call(this, identifier, product, account));
            }
            layout(layouts);

            return this;
        },
    });
});
