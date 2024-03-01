/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'uiLayout',
        'uiRegistry',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        layout,
        registry,
        rendererList
    ) {
        'use strict';

        let instantPurchaseName = 'instantPurchase';

        layout([{
            name: instantPurchaseName,
            component: 'Magento_Checkout/js/model/payment/method-group',
            title: 'Instant purchase',
            alias: 'instant-purchase',
            sortOrder: 20
        }]);

        rendererList.push(
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
                type: 'worldline_unionpay_international_securepay',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_maestro',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_mastercard',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_mastercard_debit',
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
            {
                type: 'worldline_visa_debit',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_visa_electron',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_giropay',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_ideal',
                component: 'Worldline_Connect/js/view/payment/method-renderer/method'
            },
            {
                type: 'worldline_account_to_account',
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
            },
            // LinkPlus
            {
                type: 'worldline_link_plus_payment_link',
                component: 'Worldline_Connect/js/view/payment/method-renderer/link-plus/payment-link'
            }
        );

        registry.get(instantPurchaseName, function (instantPurchase) {
            rendererList.push(
                {
                    type: 'worldline_apple_pay',
                    component: 'Worldline_Connect/js/view/payment/method-renderer/apple-pay',
                    group: instantPurchase
                },
                {
                    type: 'worldline_google_pay',
                    component: 'Worldline_Connect/js/view/payment/method-renderer/google-pay',
                    group: instantPurchase
                }
            );
        });

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
