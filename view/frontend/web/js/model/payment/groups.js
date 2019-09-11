/*browser:true*/
/*global define*/

define([
    'Ingenico_Connect/js/model/payment/products',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/model/payment/config',
    'ko'
], function (productList, fetchProduct, config, ko) {
    'use strict';

    return {

        groups: ko.observableArray([]).extend({
            rateLimit: {method: "notifyWhenChangesStop", timeout: 20}
        }),

        /**
         * Regenerate product groups according to the global product list
         */
        reload: function () {
            if (productList.basicPaymentProducts().length === 0) {
                return;
            }

            productList.isLoading(true);

            let groupTitles = config.getGroupTitles();
            let groupsMap = new Map();

            /**
             * Add token group
             */
            if (config.useInlinePayments() && productList.accountsOnFile().length > 0) {
                groupsMap.set('token', {
                    'id': 'token',
                    'name': groupTitles.token,
                    'products': [],
                    'accounts': []
                });
                for (let account of productList.accountsOnFile()) {
                    let product = productList.getById(account.paymentProductId);
                    groupsMap.get('token').products.push(product);
                    groupsMap.get('token').accounts.push(account);
                }
            }

            /**
             * Add product groups
             */
            for (let product of productList.basicPaymentProducts()) {
                if (!groupsMap.get(product.paymentMethod)) {
                    groupsMap.set(
                        product.paymentMethod,
                        {
                            'id': product.paymentMethod,
                            'name': groupTitles[product.paymentMethod],
                            'products': [product],
                            'accounts': []
                        }
                    );
                } else {
                    groupsMap.get(product.paymentMethod).products.push(product)
                }
            }

            let staticGroups = Array.from(groupsMap.values());
            this.groups(this.sortGroups(staticGroups));
            productList.isLoading(false);
        },

        sortGroups: function (groups) {
            groups.sort(function (a, b) {
                /**
                 * Sort tokens and cards first
                 */
                if (b.id === 'token' || b.id === 'card') {
                    return 1;
                }
                if (a.id === 'token' || a.id === 'card') {
                    return -1;
                }
                if (a.id === 'token' && b.id === 'card') {
                    return -1;
                }
                if (b.id === 'token' && a.id === 'card') {
                    return 1;
                }
                return 0;
            });

            return groups;
        }
    };
});
