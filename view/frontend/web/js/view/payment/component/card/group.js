define([
    'uiCollection',
    'uiLayout',
    'Ingenico_Connect/js/action/select-product',
    'Ingenico_Connect/js/model/payment/payment-data',
], function (Collection, layout, selectProduct, paymentData) {
    'use strict';

    return Collection.extend({

        defaults: {
            listens: {
                'cardGroupPaymentMethod': 'initCardPaymentGroup',
            }
        },

        initialize: function () {
            this._super();
            this.initCardPaymentGroup(this.cardGroupPaymentMethod());
            paymentData.currentProductIdentifier.subscribe(this.handleProductSelect.bind(this));
            return this;
        },

        initCardPaymentGroup: function (cardGroupPaymentMethod) {
            if (!cardGroupPaymentMethod || cardGroupPaymentMethod.length === 0) {
                return;
            }

            cardGroupPaymentMethod.allowsTokenization = true

            this.destroyChildren();
            const cardLayout = {
                name: 'group-card',
                parent: this.name,
                component: 'Ingenico_Connect/js/view/payment/component/card/method',
                isTokenGroup: false,
                additionalClasses: 'group-card',
                product: cardGroupPaymentMethod
            };

            layout([cardLayout]);
        },

        handleProductSelect: function (productIdentifier) {
            selectProduct(productIdentifier);
        },
    });
});
