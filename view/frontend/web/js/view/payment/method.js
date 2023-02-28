/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'Worldline_Connect/js/model/payment/config'
    ],
    function (
        Component,
        rendererList,
        config
    ) {
        'use strict';
        config.init(window.checkoutConfig.payment['worldline']);
        rendererList.push(
            // Cards
            {
                type: 'worldline_cards',
                component: 'Worldline_Connect/js/view/payment/method-renderer/cards'
            },
            {
                type: 'worldline_americanexpress',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_bc_card',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_cartebancaire',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_dinersclub',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_discover',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_hyundai_card',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_jcb',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_kb_kookmin_card',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_keb_hana_card',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_lotte_card',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_mastercard',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_nh_card',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_samsung_card',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_shinhan_card',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_unionpay_expresspay',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_visa',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            // Redirect
            {
                type: 'worldline_giropay',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_ideal',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_open_banking',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_paypal',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_paysafecard',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_sofort',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_trustly',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            // HPP
            {
                type: 'worldline_hpp',
                component: 'Worldline_Connect/js/view/payment/method-renderer/hpp'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
