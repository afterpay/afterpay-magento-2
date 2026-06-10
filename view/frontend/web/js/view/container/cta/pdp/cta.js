    define([
    'afterpayBaseContainer',
    'ko',
    'Magento_Catalog/js/price-utils',
    'Afterpay_Afterpay/js/service/container/cta/modal-options-updater'
], function (Component, ko, priceUtils, modalOptionsUpdater) {
    'use strict';

    return Component.extend({
        defaults: {
            dataIsEligible: "true",
            dataCbtEnabledString: "false",
            pageType: "product",
            dataAmountSelector: ".product-info-main .price-final_price .price-wrapper .price",
            dataAmountSelectorBundle: "#bundleSummary .price-as-configured .price",
            dataPlacementId: ""
        },
        initialize: function () {
            const res = this._super();
            if (!!document.querySelector(this.dataAmountSelectorBundle)) {
                this.dataAmountSelector = this.dataAmountSelectorBundle;
            }
            this.dataShowLowerLimit = this._getStringBool(this.dataShowLowerLimit);
            this.dataCbtEnabledString = this._getStringBool(this.dataCbtEnabled);
            return res;
        },
        initObservable: function () {
            const res = this._super();
            this.dataIsEligible = ko.computed(() => this._getStringBool(this.isProductAllowed()));
            this.dataAmount = ko.computed(() => this.isProductAllowed() ? priceUtils.formatPrice(this.currentPrice()) : "");
            return res;
        },
        _getIsVisible: function () {
            return true;
        },
        onRendered: function () {
            if (this.id) {
                modalOptionsUpdater(this.id, {
                    locale: this.dataLocale,
                    cbtEnabled: this.dataCbtEnabled
                });
            }
        }
    });
});
