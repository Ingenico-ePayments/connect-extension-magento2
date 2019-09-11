define([
    'jquery',
    'uiCollection',
    'uiLayout',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/model/payment/config',
    'Ingenico_Connect/js/model/payment/payment-data',
    'mage/translate'
], function ($, Collection, layout, fetchProduct, config, paymentData, $t) {
    'use strict';

    return Collection.extend({

        defaults: {
            isLoading: false,
            visible: true,
            containerVisible: true,
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
                fetchProduct(this.product.id).then(function (fullProduct) {
                    let layouts = [];
                    for (let field of fullProduct.paymentProductFields) {
                        layouts.push(this.getProductFieldLayout(field));
                    }
                    if (!this.account && this.product.allowsTokenization && !this.product.autoTokenized) {
                        layouts.push(this.getTokenizeCheckboxLayout());
                    }
                    if (fullProduct.paymentProductFields.length === 0) {
                        layouts.push(this.getRedirectInfoLayout());
                    }
                    layout(layouts);
                    this.fieldsVisiblity(true);
                    this.fieldsLoaded = true;
                    this.isLoading(false);
                }.bind(this), function (error) {
                    console.warn(error);
                    this.isLoading(false);
                }.bind(this));
            }
        },

        getProductFieldLayout: function(field) {
            return {
                parent: this.name,
                component: 'Ingenico_Connect/js/view/payment/component/field',
                field: field,
                account: this.account,
            }
        },

        getTokenizeCheckboxLayout: function() {
            return {
                parent: this.name,
                component: 'Magento_Ui/js/form/element/single-checkbox',
                elementTmpl: 'Ingenico_Connect/payment/product/field/token-checkbox',
                checkedValue: this.product.id,
                value: paymentData.tokenize,
                dataScope: this.name + '-tokenize',
                description: $t('Save for later'),
            }
        },

        getRedirectInfoLayout: function() {
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
