define([], function () {
    'use strict';

    return function () {
        let components = [
            'Netresearch_Epayments/js/view/payment/component/collection/groups',
            'Netresearch_Epayments/js/view/payment/component/collection/products',
            'Netresearch_Epayments/js/view/payment/component/product',
            'Netresearch_Epayments/js/view/payment/component/collection/fields',
            'Netresearch_Epayments/js/view/payment/component/field',
        ];
        require(components);
    }
});
