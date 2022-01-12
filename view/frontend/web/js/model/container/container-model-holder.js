define([
    'ko'
], function (ko) {
    const containerModel =
    {
        getIsVirtual: function () {
            return this.isVirtual();
        },
        setIsVirtual: function (isVirtual) {
            this.isVirtual(isVirtual);
            return this;
        },
        setPrice: function (price) {
            this.price(price);
            return this;
        },
        getPrice: function () {
            return this.price();
        },
        setCurrentProductsIds: function (currentProductsIds) {
            this.currentProductsIds(currentProductsIds);
            return this;
        },
        setCurrentProductId: function (currentProductId) {
            this.currentProductsIds([currentProductId]);
            return this;
        },
        getCurrentProductsIds: function () {
            return this.currentProductsIds().slice();
        }
    };
    return {
        getModel: function (modelId) {
            if (!this[modelId]) {
                this[modelId] = Object.assign({
                    isVirtual: ko.observable(false),
                    price: ko.observable(0),
                    id: modelId,
                    currentProductsIds: ko.observable([])
                }, containerModel);
            }
            return this[modelId];
        }
    }
});
