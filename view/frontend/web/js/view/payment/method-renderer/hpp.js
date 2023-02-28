/*browser:true*/
/*global define*/

define(
  [
      'Magento_Checkout/js/view/payment/default',
      'Magento_Checkout/js/action/redirect-on-success',
      'Worldline_Connect/js/model/payment/config'
  ],
  function (Component, redirectOnSuccessAction, config) {
      'use strict';

      return Component.extend({
          config: {},

          defaults: {
              template: 'Worldline_Connect/payment/hpp',
              code: ''
          },

          title: function () {
              return config.getHostedCheckoutTitle();
          },

          afterPlaceOrder: function () {
              redirectOnSuccessAction.redirectUrl = config.getHostedCheckoutUrl();
              this.redirectAfterPlaceOrder = true;
          },
      });
  }
);
