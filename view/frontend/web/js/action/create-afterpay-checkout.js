/**
 * @api
 */
define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
], function (quote, urlBuilder, storage, errorProcessor, fullScreenLoader) {
    'use strict';

    return function (messageContainer, redirectPath) {
        const payload = {
            cartId: quote.getQuoteId(),
            redirectPath: redirectPath
        };

        const serviceUrl = urlBuilder.createUrl('/afterpay/checkout', {});

        fullScreenLoader.startLoader();

        return storage.post(
            serviceUrl,
            JSON.stringify(payload),
            true,
            'application/json'
        ).fail(function (response) {
            errorProcessor.process(response, messageContainer);
        }).always(function () {
            fullScreenLoader.stopLoader();
        });
    };
});
