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
], function ($, Collection, layout, fetchProduct, config, paymentData, $t) {
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

                layout(this.createLayout());
                this.fieldsVisiblity(true);
                this.fieldsLoaded = true;
                this.isLoading(false);
            }
        },

        createLayout: function () {
            let layouts = [];
            layouts.push(this.getRedirectInfoLayout());
            return layouts;
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
