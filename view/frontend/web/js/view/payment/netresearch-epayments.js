/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'ingenico',
                component: 'Netresearch_Epayments/js/view/payment/method-renderer/ingenico'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
