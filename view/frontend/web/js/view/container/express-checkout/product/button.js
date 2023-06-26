define([
    'afterpayExpressCheckoutButton',
    'Magento_Customer/js/customer-data',
    'Afterpay_Afterpay/js/model/container/container-model-holder',
    'Afterpay_Afterpay/js/model/container/express-checkout-popup',
    'jquery'
], function (Component, customerData, containerHolder, expressCheckoutPopup, $) {
    'use strict';

    return Component.extend({
        defaults: {
            cart: customerData.get('cart'),
            isVirtual: false,
            cartModelContainerId: false,
        },
        initialize: function () {
            const res = this._super();
            expressCheckoutPopup.setHandler(
                this.entryPoint,
                expressCheckoutPopup.handlerNames.validation,
                this._getValidationHandler()
            );
            return res;
        },
        initObservable: function () {
            if (this.cartModelContainerId) {
                this.cartContainerModel = containerHolder.getModel(this.cartModelContainerId);
            }
            this.cart.subscribe((cart) => {
                if (!this.onCartUpdated) {
                    return ;
                }
                if (!cart.items || cart.items.length === 0) {
                    this.onCartUpdated.reject();
                }
                this.onCartUpdated.resolve();
            });
            return this._super();
        },
        _getOnCommenceCheckoutAfterpayMethod: function () {
            let isBundle = $('#product_addtocart_form').find('#bundleSummary').length;
            const parentOnCommenceCheckoutAfterpayMethod = this._super();
            return (actions) => {
                if (!isBundle) {
                    const productSubmitForm = $('#product_addtocart_form');
                    productSubmitForm.submit();
                }
                this.onCartUpdated = $.Deferred();
                this.onCartUpdated.done(() => parentOnCommenceCheckoutAfterpayMethod(actions))
                    .fail(() => this._fail(actions, AfterPay.constants.SERVICE_UNAVAILABLE));
            }
        },
        _getOnComplete: function () {
            const parentOnComplete = this._super();
            return function (event) {
                if (event.data.status === 'CANCELLED') {
                    window.location.href = window.checkout.shoppingCartUrl;
                }
                return parentOnComplete(event);
            }
        },
        _getIsVirtual: function () {
            if (this.cartContainerModel) {
                const isCartEmpty = !this.cart().items || this.cart().items.length === 0;
                return (isCartEmpty && this.isVirtual) ||
                    (this.cartContainerModel.getIsVirtual() && this.isVirtual);
            }
            return this.containerModel.getIsVirtual();
        },
        _getValidationHandler: function () {
            return () => {
                const productSubmitForm = $('#product_addtocart_form');
                const pdpButtonForm = $('#product-addtocart-button');
                return pdpButtonForm.length > 0 && productSubmitForm.length > 0 && productSubmitForm.valid();
            }
        },
        _getIsVisible: function () {
            return $('#product-addtocart-button').length > 0 && this._super();
        },
        _getIsAllProductsAllowed: function () {
            let isProductsInCartAllowed = true;
            if (this.cartContainerModel) {
                isProductsInCartAllowed = this._getIsAllProductsInArrayAllowed(this.cartContainerModel.getCurrentProductsIds())
            }
            if (!isProductsInCartAllowed) {
                return false;
            }
            return this._super()
        }
    });
});
