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
                'productGroups': 'initProductGroups',
            }
        },

        initialize: function () {
            this._super();
            this.initProductGroups(this.productGroups());
            paymentData.currentProductIdentifier.subscribe(this.handleProductSelect.bind(this));
            return this;
        },

        initProductGroups: function (productGroups) {
            if (productGroups.length === 0) {
                return;
            }
            this.destroyChildren();
            let layouts = [];
            for (let group of productGroups) {
                let isTokenGroup = (group.id === 'token');
                layouts.push({
                    name: 'group-' + group.id,
                    parent: this.name,
                    component: 'Ingenico_Connect/js/view/payment/component/collection/products',
                    isTokenGroup: isTokenGroup,
                    additionalClasses: 'group-' + group.id,
                    products: group.products,
                    accounts: group.accounts,
                });
            }

            layout(layouts);
        },

        handleProductSelect: function (productIdentifier) {
            selectProduct(productIdentifier);
        }
    });
});
