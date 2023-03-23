define([
    'afterpayBaseContainer',
    'Afterpay_Afterpay/js/model/container/express-checkout-popup',
    'ko',
    'mage/url',
    'jquery',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/view/messages',
    'jquery/jquery-storageapi'
], function (Component, expressCheckoutPopup, ko, url, $, $t, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            minOrderTotal: 0,
            maxOrderTotal: 0,
            countryCode: ''
        },
        initialize: function () {
            const res = this._super();
            expressCheckoutPopup.setHandler(
                this.entryPoint,
                expressCheckoutPopup.handlerNames.commenceCheckout,
                this._getOnCommenceCheckoutAfterpayMethod()
            );
            expressCheckoutPopup.setHandler(
                this.entryPoint,
                expressCheckoutPopup.handlerNames.shippingAddressChange,
                this._getOnShippingAddressChange()
            );
            expressCheckoutPopup.setHandler(
                this.entryPoint,
                expressCheckoutPopup.handlerNames.complete,
                this._getOnComplete()
            );
            let errorMessage = $.localStorage.get('express-error-message');
            if (errorMessage) {
                customerData.set('messages', {
                    messages: [{
                        type: 'error',
                        text: $t(errorMessage)
                    }]
                });
                $.localStorage.remove('express-error-message');
            }
            return res;
        },
        initAfterpay: function () {
            expressCheckoutPopup.initAfterpayPopup(this.countryCode);
        },
        _getOnCommenceCheckoutAfterpayMethod: function () {
            return (actions) => {
                AfterPay.shippingOptionRequired = !this._getIsVirtual();
                $.post(
                    url.build('afterpay/express/createCheckout')
                ).done((response) => {
                    if (response && response.afterpay_token) {
                        actions.resolve(response.afterpay_token);
                    } else {
                        this._fail(actions, AfterPay.constants.SERVICE_UNAVAILABLE);
                    }
                }).fail(
                    () => this._fail(actions, AfterPay.constants.SERVICE_UNAVAILABLE)
                );
            }
        },
        _getOnShippingAddressChange: function () {
            return function (shippingAddress, actions) {
                $.post(
                    url.build('afterpay/express/getShippingOptions'),
                    shippingAddress
                ).done((response) => {
                    if (response.success && Array.isArray(response.shippingOptions)) {
                        actions.resolve(response.shippingOptions);
                    } else {
                        this._fail(actions, AfterPay.constants.SHIPPING_ADDRESS_UNSUPPORTED);
                    }
                }).fail(
                    () => this._fail(actions, AfterPay.constants.SHIPPING_ADDRESS_UNRECOGNIZED)
                );
            }
        },
        _getOnComplete: function () {
            return function (event) {
                if (event.data.status === 'CANCELLED') {
                    return;
                }

                $(document.body).trigger('processStart');
                $.post(
                    url.build('afterpay/express/placeOrder'),
                    event.data
                ).done(function (response) {
                    if (response && response.redirectUrl) {
                        if (response.error) {
                            $.localStorage.set('express-error-message', response.error);
                        }
                        $.mage.redirect(response.redirectUrl);
                    } else {
                        $(document.body).trigger('processStop');
                    }
                });
            }
        },
        _fail: function(actions, afterpayConst) {
            actions.reject(afterpayConst);
            AfterPay.close();
        },
        _getIsVirtual: function () {
            return this.containerModel.getIsVirtual();
        },
        _getIsVisible: function () {
            const floatMaxOrderTotal = parseFloat(this.maxOrderTotal);
            const floatMinOrderTotal = parseFloat(this.minOrderTotal);

            return (this.countryCode && window.AfterPay !== undefined && this.isProductAllowed() &&
                !(this.currentPrice() > floatMaxOrderTotal || this.currentPrice() < floatMinOrderTotal) &&
                !this._getIsVirtual()) && this._super();
        }
    });
});
