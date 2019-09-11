define([], function () {
    'use strict';

    return function () {
        let components = [
            'Ingenico_Connect/js/view/payment/component/collection/groups',
            'Ingenico_Connect/js/view/payment/component/collection/products',
            'Ingenico_Connect/js/view/payment/component/product',
            'Ingenico_Connect/js/view/payment/component/collection/fields',
            'Ingenico_Connect/js/view/payment/component/field',
        ];
        require(components);
    }
});
