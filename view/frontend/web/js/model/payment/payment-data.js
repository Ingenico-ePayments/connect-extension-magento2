/*browser:true*/
/*global define*/

define(['ko'],
    function (ko) {
        'use strict';

        let currentProductIdentifier = ko.observable('');
        let currentPaymentProduct = ko.observable(false);
        let currentCardPaymentProduct = ko.observable(false);
        let currentAccountOnFile = ko.observable(false);
        let currentPayload = ko.observable(false);
        let fieldData = {};
        let tokenize = ko.observableArray([]);
        let token = ko.observable('');

        return {
            currentProductIdentifier: currentProductIdentifier,

            currentCardPaymentProduct: currentCardPaymentProduct,

            fieldData: fieldData,

            tokenize: tokenize,

            token: token,

            setCurrentProductIdentifier: function (value) {
                currentProductIdentifier(value)
            },

            setCurrentPaymentProduct: function (value) {
                currentPaymentProduct(value)
            },

            setCurrentCardPaymentProduct: function (value) {
                currentCardPaymentProduct(value)
            },

            setCurrentAccountOnFile: function (value) {
                currentAccountOnFile(value)
            },

            setCurrentPayload: function (value) {
                currentPayload(value)
            },

            setToken: function (value) {
                token(value)
            },

            getCurrentProductIdentifier: function () {
                return currentProductIdentifier()
            },

            getCurrentPaymentProduct: function () {
                return currentPaymentProduct()
            },

            getCurrentCardPaymentProduct: function() {
                return currentCardPaymentProduct()
            },

            getCurrentAccountOnFile: function () {
                return currentAccountOnFile()
            },

            getCurrentPayload: function () {
                return currentPayload()
            },

            getToken: function() {
                return token()
            }
        }
    }
);
