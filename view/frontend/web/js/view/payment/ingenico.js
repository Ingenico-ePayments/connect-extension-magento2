/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'uiLayout',
        'uiRegistry',
        'Magento_Checkout/js/model/payment/renderer-list',
        'Ingenico_Connect/js/model/payment/config'
    ],
    function (
        Component,
        layout,
        registry,
        rendererList,
        config
    ) {
        'use strict';
        config.init(window.checkoutConfig.payment['ingenico']);

        let instantPurchaseName = 'instantPurchase';

        layout([{
            name: instantPurchaseName,
            component: 'Magento_Checkout/js/model/payment/method-group',
            alias: 'instant-purchase',
            sortOrder: 20
        }]);

        registry.get(instantPurchaseName, function (instantPurchase) {
            rendererList.push(
                // Add rendering of payment products here
            );
        });

        rendererList.push(
            // Cards
            {
                type: 'ingenico_cards',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/cards'
            },
            {
                type: 'ingenico_americanexpress',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_cartebancaire',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_mastercard',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_visa',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            // Redirect
            {
                type: 'ingenico_giropay',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_ideal',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_open_banking',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_paypal',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_paysafecard',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_sofort',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'ingenico_trustly',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/method'
            },
            // HPP
            {
                type: 'ingenico_hpp',
                component: 'Ingenico_Connect/js/view/payment/method-renderer/hpp'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
