/*browser:true*/
/*global define*/

define(
  [
      'Magento_Checkout/js/view/payment/default',
      'Magento_Checkout/js/action/redirect-on-success',
      'Worldline_Connect/js/model/payment/config',
      'Worldline_Connect/js/model/payment/payment-data'
  ],
  function (Component, redirectOnSuccessAction, config, paymentData) {
      'use strict';

      return Component.extend({
          config: {},

          defaults: {
              template: 'Worldline_Connect/payment/hpp',
              code: ''
          },

          afterPlaceOrder: function () {
              if (paymentData.getCurrentPayload()) {
                  redirectOnSuccessAction.redirectUrl = config.getInlineSuccessUrl();
              } else {
                  redirectOnSuccessAction.redirectUrl = config.getHostedCheckoutUrl();
              }
              this.redirectAfterPlaceOrder = true;
          },
      });
  }
);
