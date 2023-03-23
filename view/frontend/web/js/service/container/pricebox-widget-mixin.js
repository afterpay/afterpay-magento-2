define([
    'jquery',
    "Afterpay_Afterpay/js/model/container/container-model-holder"
], function ($, containerModelHolder) {
    'use strict';
    const containerModel = containerModelHolder.getModel("afterpay-pdp-container");
    const priceBoxWidget = {
        _checkIsFinalPriceDefined: function () {
            return this.cache.displayPrices && this.cache.displayPrices.finalPrice && this.cache.displayPrices.finalPrice.formatted;
        },
        updatePrice: function (newPrices) {
            const res = this._super(newPrices);
            if (this._checkIsFinalPriceDefined() && this.element.closest('product-info-main')) {
                containerModel.setCurrentProductId(this.element.data('productId'));
                containerModel.setPrice(this.cache.displayPrices.finalPrice.amount);
            }
            return res;
        }
    };
    return function (targetWidget) {
        $.widget('mage.priceBox', targetWidget, priceBoxWidget);

        return $.mage.priceBox;
    };
});
