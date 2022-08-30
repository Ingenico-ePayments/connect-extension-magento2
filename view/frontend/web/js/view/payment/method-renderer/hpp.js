/*browser:true*/
/*global define*/

define(
  [
      'Magento_Checkout/js/view/payment/default',
      'Magento_Checkout/js/action/redirect-on-success',
      'Ingenico_Connect/js/model/payment/config'
  ],
  function (Component, redirectOnSuccessAction, config) {
      'use strict';

      return Component.extend({
          config: {},

          defaults: {
              template: 'Ingenico_Connect/payment/hpp',
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
