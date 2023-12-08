define([
    'jquery',
    'uiCollection',
    'uiLayout',
    'Worldline_Connect/js/action/get-payment-product',
    'Worldline_Connect/js/model/payment/config',
    'Worldline_Connect/js/model/payment/payment-data',
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

        /**
         * @public
         */
        initFields: function () {
            this.initLoader();
            this.fieldsVisiblity(true);

            if (!this.fieldsLoaded) {
                this.isLoading(true);

                if (this.product.paymentProductFields && this.product.paymentProductFields.length) {
                    layout(this.createLayout(this.product));
                    this.fieldsVisiblity(true);
                    this.fieldsLoaded = true;
                    this.isLoading(false);
                    return;
                }

                fetchProduct(this.product.id).then(function (fullProduct) {
                    layout(this.createLayout(fullProduct));

                    this.fieldsVisiblity(true);
                    this.fieldsLoaded = true;
                    this.isLoading(false);
                }.bind(this), function (error) {
                    console.warn(error);
                    this.isLoading(false);
                }.bind(this));
            }
        },

        createLayout: function (product) {
            let layouts = [];
            for (let field of product.paymentProductFields) {
                layouts.push(this.getProductFieldLayout(field, product));
            }
            if (!this.account && this.product.allowsTokenization && !this.product.autoTokenized && config.isCustomerLoggedIn() && config.saveForLaterVisible()) {
                layouts.push(this.getTokenizeCheckboxLayout());
            }
            if (product.paymentProductFields.length === 0) {
                layouts.push(this.getRedirectInfoLayout());
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
                component: 'Worldline_Connect/js/view/payment/component/field',
                field: field,
                account: this.account,
            }
        },

        getCustomProductFieldLayout: function (field, product) {
            if (product.id === 'cards' && field.id === 'cardNumber') {
                return {
                    parent: this.name,
                    component: 'Worldline_Connect/js/view/payment/component/card/field/cardnumber',
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
                elementTmpl: 'Worldline_Connect/payment/product/field/token-checkbox',
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
                template: 'Worldline_Connect/payment/product/field/info',
                text: config.redirectText(),
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
