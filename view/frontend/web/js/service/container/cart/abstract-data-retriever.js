define([
    'uiComponent',
    'ko',
    'Afterpay_Afterpay/js/model/container/container-model-holder',
    'Magento_Customer/js/customer-data'
], function (Component, ko, containerModelHolder, customerData) {
    return Component.extend({
        defaults: {
            modelContainerId: "none",
            isVisible: false,
            dataAmount: "0",
            cartModel: customerData.get('cart')
        },
        initialize: function () {
            const res = this._super();
            this._updateContainerModel(this.modelWithPrice());
            this._cartUpdate(this.cartModel());
            return res;
        },
        _updateContainerModel: function (modelWithPrice) {
            const containerModel = containerModelHolder.getModel(this.modelContainerId);
            if (modelWithPrice[this.priceKey] && parseFloat(modelWithPrice[this.priceKey]) > 0) {
                containerModel.setPrice(modelWithPrice[this.priceKey]);
            } else {
                containerModel.setPrice(0);
            }
        },
        _cartUpdate: function (newCart) {
            const containerModel = containerModelHolder.getModel(this.modelContainerId);
            if (newCart && Array.isArray(newCart.items)) {
                containerModel.setCurrentProductsIds(newCart.items.map((item) => item.product_id));
                containerModel.setIsVirtual(newCart.items.reduce((isVirtual, item) => isVirtual && item.is_virtual, true));
            }
        },
        initObservable: function () {
            const res = this._super();
            this.modelWithPrice.subscribe(this._updateContainerModel, this)
            this.cartModel.subscribe(this._cartUpdate, this)
            return res;
        }
    });
});
