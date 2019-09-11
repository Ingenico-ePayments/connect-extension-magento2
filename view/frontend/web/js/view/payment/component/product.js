define([
    'Magento_Ui/js/form/element/abstract',
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Ingenico_Connect/payment/product/radio',
            input_type: 'radio',
            logo: '',
            checked: '',
        },

        initialize: function () {
            this._super();

            this.value = this.index;
            this.logo = this.product.displayHints.logo + '?size=120x80';
        },
    })
});
