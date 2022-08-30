/*browser:true*/
/*global define*/

define(
  [
      'Magento_Checkout/js/view/payment/default',
      'Magento_Checkout/js/action/redirect-on-success',
      'Ingenico_Connect/js/model/payment/config',
      'Ingenico_Connect/js/model/payment/payment-data'
  ],
  function (Component, redirectOnSuccessAction, config, paymentData) {
      'use strict';

      return Component.extend({
          config: {},

          defaults: {
              template: 'Ingenico_Connect/payment/hpp',
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
