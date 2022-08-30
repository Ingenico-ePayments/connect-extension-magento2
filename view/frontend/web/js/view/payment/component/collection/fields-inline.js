define([
    'jquery',
    'uiCollection',
    'uiLayout',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/model/payment/config',
    'Ingenico_Connect/js/model/payment/payment-data',
    'mage/translate',
    'uiRegistry',
    'ko'
], function ($, Collection, layout, fetchProduct, config, paymentData, $t, registry, ko) {
    'use strict';

    return Collection.extend({

        defaults: {
            isLoading: false,
            visible: true,
            containerVisible: false,
            fieldsLoaded: false,
        },

        initObservable() {
            return this._super()
                .observe([
                    'visible',
                    'containerVisible',
                    'isLoading',
                ]);
        },

        initialize: function () {
            this._super();
            this.initLoader();
            this.fieldsVisiblity(true);

            if (!this.fieldsLoaded) {
                this.isLoading(true);

                layout(this.createLayout(this.product));
                this.fieldsVisiblity(true);
                this.fieldsLoaded = true;
                this.isLoading(false);
            }
        },

        createLayout: function (product) {
            let layouts = [];
            if (product.paymentProductFields.length === 0) {
                layouts.push(this.getRedirectInfoLayout());
            } else {
                for (let field of product.paymentProductFields) {
                    layouts.push(this.getProductFieldLayout(field, product));
                }
            }

            if (!this.account && product.allowsTokenization && !product.autoTokenized && config.isCustomerLoggedIn() && config.saveForLaterVisible()) {
                layouts.push(this.getTokenizeCheckboxLayout());
            }

            return layouts;
        },

        getProductFieldLayout: function (field, product) {
            const customProductFieldLayout = this.getCustomProductFieldLayout(field, product);
            if (customProductFieldLayout) {
                return customProductFieldLayout;
            }

            return {
                parent: this.name,
                component: 'Ingenico_Connect/js/view/payment/component/field',
                field: field,
                account: this.account,
            }
        },

        getCustomProductFieldLayout: function (field, product) {
            if (product.id === 'cards' && field.id === 'cardNumber') {
                return {
                    parent: this.name,
                    component: 'Ingenico_Connect/js/view/payment/component/card/field/cardnumber',
                    field: field,
                    account: this.account,
                }
            }
        },

        getTokenizeCheckboxLayout: function () {
            // Tokenization can change per card:
            // Also see cardnumber.js
            if (this.product.id === 'cards') {
                registry.set('cardAllowsTokenization', ko.observable(false));
            }
            let cardAllowsTokenization = registry.get('cardAllowsTokenization');

            return {
                parent: this.name,
                component: 'Magento_Ui/js/form/element/single-checkbox',
                elementTmpl: 'Ingenico_Connect/payment/product/field/token-checkbox',
                checkedValue: this.product.id,
                cardAllowsTokenization: cardAllowsTokenization,
                value: paymentData.tokenize,
                enabled: this.product.id === 'cards' ? cardAllowsTokenization : true,
                dataScope: this.name + '-tokenize',
                description: $t('Save for later'),
            }
        },

        getRedirectInfoLayout: function () {
            return {
                parent: this.name,
                component: 'Magento_Ui/js/lib/core/element/element',
                template: 'Ingenico_Connect/payment/product/field/info',
                text: $t('You will be redirected to enter your payment details.'),
            }
        },

        /**
         * @public
         * @param {boolean} bool
         */
        fieldsVisiblity: function (bool) {
            this.containerVisible(bool)
        },

        /**
         * @private
         */
        initLoader: function () {
            const loaderContainer = $('[value=' + this.uid + ']').first().parent();
            loaderContainer.loader({
                icon: config.getLoaderImage(),
                template:
                    '<div class="loading-mask" data-role="loader" style="position:absolute;">' +
                    '<div class="loader">' +
                    '<img src="<%- data.icon %>" style="position:absolute">' +
                    '</div>' +
                    '</div>',
            });
            this.isLoading.subscribe(function (isLoading) {
                if (isLoading) {
                    loaderContainer.trigger('processStart');
                } else {
                    loaderContainer.trigger('processStop');
                }
            })
        },
    });
});
