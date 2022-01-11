define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    return {
        canDisplayOnMinicart: function () {
            return !this._isCartEmpty() && !this._isCartVirtual();
        },

        canDisplayOnPDP: function (isProductVirtual) {
            if (this._isCartEmpty()) {
                return !isProductVirtual;
            }
            return (!isProductVirtual || !this._isCartVirtual()) && !this._doesCartHaveRestricted()  ;
        },

        _isCartEmpty: function () {
            const cartItems = customerData.get('cart')().items;
            return !cartItems || cartItems.length < 1;
        },

        _isCartVirtual: function () {
            if (this._isCartEmpty()) {
                return false;
            }
            const cartItems = customerData.get('cart')().items;
            let virtualProductsInCart = 0;
            cartItems.forEach(function (val) {
                if (val.is_virtual) {
                    virtualProductsInCart++;
                }
            });
            return cartItems.length === virtualProductsInCart;
        },

        _doesCartHaveRestricted: function () {
            if (this._isCartEmpty()) {
                return false;
            }
            const cartItems = customerData.get('cart')().items;
            return cartItems.reduce(function (hasRestricted, item) {
                return hasRestricted || item.afterpay_restricted;
            }, false);
        }
    }
});
