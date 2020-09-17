define([
    'jquery',
    'uiCollection',
    'uiLayout',
    'Ingenico_Connect/js/action/get-payment-product',
    'Ingenico_Connect/js/model/payment/config',
], function ($, Collection, layout, fetchProduct, config) {
    'use strict';

    return Collection.extend({

        defaults: {
            isLoading: false,
            visible: true,
            containerVisible: false,
            tooltipsLoaded: false,
        },

        initObservable() {
            return this._super()
                .observe([
                    'visible',
                    'containerVisible',
                    'isLoading',
                ]);
        },

        initTooltips: function () {
            this.initLoader();
            const tooltips = this.getTooltipComponents(this.product.id);
            if (tooltips.length > 0) {
                this.initLoader();
                this.fieldsVisiblity(true);

                if (!this.tooltipsLoaded) {
                    this.isLoading(true);
                    layout(this.createLayout(tooltips))
                    this.tooltipsLoaded = true;
                    this.isLoading(false);
                }
            }
        },

        createLayout: function (tooltips) {
            let layouts = [];
            for (let tooltip of tooltips) {
                layouts.push(this.getTooltipLayout(tooltip));
            }

            return layouts;
        },

        getTooltipLayout: function (tooltip) {
            return {
                parent: this.name,
                component: tooltip
            }
        },

        getTooltipComponents: function (productId) {
            switch (productId) {
                case 'cards':
                    return ['Ingenico_Connect/js/view/payment/component/card/tooltip/cards'];
                default:
                    return [];
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
