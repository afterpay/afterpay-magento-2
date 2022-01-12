define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'afterpay',
            component: 'Afterpay_Afterpay/js/view/payment/method-renderer/afterpay'
        }
    );

    return Component.extend({});
});
