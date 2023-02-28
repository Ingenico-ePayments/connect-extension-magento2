define([], function () {
    'use strict';

    return function () {
        let components = [
            'Worldline_Connect/js/view/payment/component/collection/groups',
            'Worldline_Connect/js/view/payment/component/collection/products',
            'Worldline_Connect/js/view/payment/component/product',
            'Worldline_Connect/js/view/payment/component/collection/fields',
            'Worldline_Connect/js/view/payment/component/field',
        ];
        require(components);
    }
});
