define([
    'uiComponent',
    'ko',
    'Afterpay_Afterpay/js/model/container/container-model-holder',
], function (Component, ko, containerModelHolder) {
    'use strict';

    return Component.extend({
        defaults: {
            currentProductsModelId: "none",
            modelContainerId: "none",
            currentPrice: 0,
            isProductAllowed: "true",
            isVisible: false,
            notAllowedProducts: []
        },
        initialize: function () {
            const res = this._super();
            this.dataShowLowerLimit = this._getStringBool(this.dataShowLowerLimit);
            return res;
        },
        initObservable: function () {
            const res = this._super();
            this.containerModel = containerModelHolder.getModel(this.modelContainerId);
            this.isProductAllowed = ko.computed(this._getIsAllProductsAllowed.bind(this));
            this.currentPrice = ko.computed(() => this.containerModel.getPrice());
            this.isVisible = ko.computed(this._getIsVisible.bind(this));
            return res;
        },
        _getStringBool: function (value) {
            return value ? "true" : "false";
        },
        _getIsVisible: function () {
            return this.currentPrice() !== 0;
        },
        _getIsAllProductsAllowed: function () {
            return this._getIsAllProductsInArrayAllowed(this.containerModel.getCurrentProductsIds());
        },
        _getIsAllProductsInArrayAllowed: function (array) {
            return array.reduce(
                (isAllowed, productId) => isAllowed && !this.notAllowedProducts.includes(productId.toString()),
                true
            );
        }
    });
});
