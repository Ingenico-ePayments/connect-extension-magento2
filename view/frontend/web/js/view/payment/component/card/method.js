define([
    'underscore',
    'Magento_Ui/js/form/components/group',
    'Ingenico_Connect/js/model/payment/payment-data',
    'Ingenico_Connect/js/model/payment/config',
    'uiLayout',
], function (_, Group, paymentData, config, layout) {
    'use strict';

    let generateProductComponent = function (identifier, description, product, account) {
        return {
            parent: this.name,
            name: identifier,
            additionalClasses: identifier,
            component: 'Ingenico_Connect/js/view/payment/component/product',
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
            component: 'Ingenico_Connect/js/view/payment/component/collection/fields',
            template: 'Ingenico_Connect/payment/product/field-collection',
            uid: identifier,
            product: product,
            account: account,
        };
    };

    let generateProductTooltips = function (identifier, product) {
        return {
            parent: this.name,
            name: identifier + '-tooltips',
            component: 'Ingenico_Connect/js/view/payment/component/collection/tooltips',
            template: 'Ingenico_Connect/payment/product/field-collection',
            uid: identifier,
            product: product,
        }
    };

    return Group.extend({

        defaults: {
            template: 'Ingenico_Connect/payment/product/group',
            isTokenGroup: false,
        },

        initialize: function () {
            this._super();
            this.initChildren();

            return this;
        },

        initChildren: function () {
            const layouts = [];

            let account;
            let identifier = 'product-' + this.product.id;
            let description = this.product.displayHints.label;

            if (this.isTokenGroup) {
                for (let account of this.product.accountsOnFile) {
                    identifier = 'token-' + account.id;
                    description = account.getMaskedValueByAttributeKey('alias').formattedValue;
                    layouts.push(generateProductComponent.call(this, identifier, description, this.product, account));
                    layouts.push(generateProductFields.call(this, identifier, this.product, account));
                }

                return;
            }

            layouts.push(generateProductComponent.call(this, identifier, description, this.product, account));
            layouts.push(generateProductFields.call(this, identifier, this.product, account));
            if (!config.useInlinePayments(paymentData.getCurrentPaymentProduct())) {
                layouts.push(generateProductTooltips.call(this, identifier, this.product));
            }

            _.defer(function(){
                layout(layouts);
            });


            return this;
        },
    });
});
