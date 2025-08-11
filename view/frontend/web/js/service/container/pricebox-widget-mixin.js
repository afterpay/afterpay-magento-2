define([
    'jquery',
    "Afterpay_Afterpay/js/model/container/container-model-holder"
], function ($, containerModelHolder) {
    'use strict';
    const containerModel = containerModelHolder.getModel("afterpay-pdp-container");
    const priceBoxWidget = {
        _checkIsFinalPriceDefined: function () {
            return !!(
                this.cache.displayPrices &&
                this.cache.displayPrices.finalPrice &&
                this.cache.displayPrices.finalPrice.formatted
            );
        },
        updatePrice: function (newPrices) {
            const res = this._super(newPrices);
            let amountSelector = ".product-info-main .price-final_price .price-wrapper .price";
            const amountSelectorBundle = "#bundleSummary .price-as-configured .price";
            let price = 0;
            if (this._checkIsFinalPriceDefined()) {
                price = this.cache.displayPrices.finalPrice.amount;
            } else {
                if (!!document.querySelector(amountSelectorBundle)) {
                    amountSelector = amountSelectorBundle;
                }
                let priceData = $(amountSelector).first().text();
                price = parseFloat(priceData.replace(/[^0-9.]/g, ''));
            }

            if (this.element.closest('.product-info-main').length > 0 ||
                this.element.closest('.bundle-options-container').length > 0) {
                containerModel.setCurrentProductId(this.element.data('productId'));
                containerModel.setPrice(price);
            }

            return res;
        }
    };
    return function (targetWidget) {
        $.widget('mage.priceBox', targetWidget, priceBoxWidget);

        return $.mage.priceBox;
    };
});
