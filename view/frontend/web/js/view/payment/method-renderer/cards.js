/*browser:true*/
/*global define*/

define(
  [
      'jquery',
      'connect-sdk',
      'underscore',
      'Magento_Checkout/js/view/payment/default',
      'uiLayout',
      'uiRegistry',
      'Ingenico_Connect/js/model/payment/payment-data',
      'Ingenico_Connect/js/action/create-payload',
      'Magento_Checkout/js/action/redirect-on-success',
      'Ingenico_Connect/js/model/payment/config',
      'Ingenico_Connect/js/action/get-card-payment-group',
      'ko',
      'Magento_Checkout/js/model/quote'
  ],
  function ($, sdk, _, Component, layout, registry, paymentData, createPayload, redirectOnSuccessAction, config, getCardPaymentGroup, ko, quote) {
      'use strict';

      return Component.extend({
          config: {},

          defaults: {
              code: '',
              title: null,
              logo: null,
              currentCountry: '',
              product: null,
              template: 'Ingenico_Connect/payment/method'
          },

          initialize: function () {
              this._super();

              this.title = ko.observable('');
              this.logo = ko.observable('');

              this.initChildren();

              return this;
          },

          initChildren: function () {

              this._super();

              let code = this.getCode();
              let name = this.name;
              let me = this;

              this.code = code;

              quote.billingAddress.subscribe(function (address) {
                  if (!address || $.isEmptyObject(address)) {
                      return;
                  }

                  let billingCountry = address.countryId;
                  if (billingCountry === me.currentCountry) {
                      return;
                  }

                  me.currentCountry = billingCountry;

                  let product = window.checkoutConfig.payment.ingenico.products[code];
                  getCardPaymentGroup().then(function(productResponse) {
                      me.title(productResponse.displayHints.label);
                      me.logo(productResponse.displayHints.logo);
                      if (product.hosted) {
                          layout([{
                              component: 'Ingenico_Connect/js/view/payment/component/collection/hosted',
                              uid: 'ingenico-' + code + '-fields',
                              displayArea: 'ingenico-cc-fields',
                              parent: name,
                              template: 'Ingenico_Connect/payment/product/field-collection'
                          }]);
                      } else {
                          layout([{
                              component: 'Ingenico_Connect/js/view/payment/component/collection/fields-inline',
                              uid: 'ingenico-' + code + '-fields',
                              displayArea: 'ingenico-cc-fields',
                              parent: name,
                              template: 'Ingenico_Connect/payment/product/field-collection',
                              product: productResponse
                          }]);
                      }
                  });
              }.bind(this));

              return this;
          },

          /**
           * @private
           */
          createPayload: function () {
              let data = this.assemblePayloadData();

              return createPayload(data).then(function (payload) {
                  paymentData.setCurrentPayload(payload);
              });
          },

          /**
           * @private
           * @return {{}}
           */
          assemblePayloadData: function () {
              let data = {};

              data['paymentProduct'] = paymentData.getCurrentCardPaymentProduct();
              if (paymentData.getCurrentAccountOnFile()) {
                  data['accountOnFile'] = paymentData.getCurrentAccountOnFile();
              }
              data = Object.assign(paymentData.fieldData, data);

              return data;
          },

          validate: function () {
              paymentData.fieldData = {};

              let product = window.checkoutConfig.payment.ingenico.products[this.code];
              if (product.hosted) {
                  return true;
              }

              let fieldsValid = true;
              let activeFieldsCollection = registry.get('uid = ingenico-' + this.code + '-fields');
              for (let fieldComponent of activeFieldsCollection.elems()) {
                  if (fieldComponent.field) {
                      paymentData.fieldData[fieldComponent.field.id] = fieldComponent.value();
                      if (!fieldComponent.validate().valid) {
                          fieldsValid = false;
                      }
                  }
              }

              return fieldsValid;
          },

          getData: function () {
              return {
                  'method': this.item.method,
                  'additional_data': {
                      'input': paymentData.getCurrentPayload(),
                      'product': paymentData.getCurrentCardPaymentProduct().id
                  }
              };
          },

          placeOrder: function (data, event) {
              let parentMethod = this._super.bind(this);
              paymentData.setCurrentPaymentProduct(this.product);
              if (!this.validate()) {
                  return false;
              }
              let product = window.checkoutConfig.payment.ingenico.products[this.code];
              if (product.hosted) {
                  parentMethod(data, event);
              } else {
                  this.createPayload().then(function () {
                      parentMethod(data, event);
                  }, function (error) {
                      console.error('Could not create payload.', error);
                      alert('Payment error: ' + error);
                  });
              }
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
