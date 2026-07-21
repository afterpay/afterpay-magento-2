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
                this._handleError(errorMessage);
            }

            return res;
        },
        initAfterpay: function () {
            expressCheckoutPopup.initAfterpayPopup(this.countryCode);
            if (this.countryCode === 'US') {
                $('.afterpay-express-button').css('background', '#101010')
            }
        },
        _getOnCommenceCheckoutAfterpayMethod: function () {
            return (actions) => {
                AfterPay.shippingOptionRequired = !this._getIsVirtual();
                $.post(
                    url.build('afterpay/express/createCheckout'),
                    {express_attempt: this.activeExpressAttempt}
                ).done((response) => {
                    if (response && response.afterpay_token) {
                        actions.resolve(response.afterpay_token);
                    } else {
                        const pendingMessage = response && response.message ? response.message : null;
                        this._revertPdpAttempt().always(() => {
                            if (pendingMessage) {
                                this._displayErrorMessage(pendingMessage);
                            }
                            this._fail(actions, Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                        });
                    }
                }).fail(
                    () => this._revertPdpAttempt().always(() => {
                        this._fail(actions, Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                    })
                );
            }
        },
        _getOnShippingAddressChange: function () {
            return (shippingAddress, actions) => {
                $.post(
                    url.build('afterpay/express/getShippingOptions'),
                    shippingAddress
                ).done((response) => {
                    if (response.success && Array.isArray(response.shippingOptions)) {
                        actions.resolve(response.shippingOptions);
                    } else {
                        this._revertPdpAttempt().always(() => {
                            this._fail(actions, Square.Marketplace.constants.SHIPPING_ADDRESS_UNSUPPORTED);
                        });
                    }
                }).fail(
                    () => this._revertPdpAttempt().always(() => {
                        this._fail(actions, Square.Marketplace.constants.SHIPPING_ADDRESS_UNRECOGNIZED);
                    })
                );
            };
        },
        _getOnComplete: function () {
            return (event) => {
                if (event.data.status === 'CANCELLED') {
                    return;
                }

                $(document.body).trigger('processStart');
                const data = Object.assign({}, event.data, {
                    express_attempt: this.activeExpressAttempt
                });
                $.post(
                    url.build('afterpay/express/placeOrder'),
                    data
                ).done(function (response) {
                    if (response && response.redirectUrl) {
                        if (response.error) {
                            $.localStorage.set('express-error-message', response.error);
                        }
                        $.mage.redirect(response.redirectUrl);
                    } else {
                        $(document.body).trigger('processStop');
                    }
                }).fail(() => {
                    $(document.body).trigger('processStop');
                    this._revertPdpAttempt();
                });
            };
        },
        _revertPdpAttempt: function () {
            if (!this.activeExpressAttempt) {
                return $.Deferred().resolve().promise();
            }

            return $.post(
                url.build('afterpay/express/revertPdp'),
                {express_attempt: this.activeExpressAttempt}
            ).always(() => {
                this.activeExpressAttempt = null;
                customerData.reload(['cart'], true);
            });
        },
        _fail: function (actions, afterpayConst) {
            actions.reject(afterpayConst);
            AfterPay.close();
        },
        _getIsVirtual: function () {
            return this.containerModel.getIsVirtual();
        },
        _getIsVisible: function () {
            const floatMaxOrderTotal = parseFloat(this.maxOrderTotal);
            const floatMinOrderTotal = parseFloat(this.minOrderTotal);

            return (this.countryCode && window.Square !== undefined && this.isProductAllowed() &&
                !(this.currentPrice() > floatMaxOrderTotal || this.currentPrice() < floatMinOrderTotal) &&
                !this._getIsVirtual()) && this._super();
        },
        _displayErrorMessage: function (errorMessage) {
            var bannerId = 'afterpay-express-error-banner';
            var escapeHtml = function (value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            };
            var banner = document.getElementById(bannerId);

            if (!banner) {
                banner = document.createElement('div');
                banner.id = bannerId;
                banner.className = 'messages';
                banner.setAttribute('role', 'alert');
                var anchor = document.getElementById('messages')
                    || document.querySelector('.page.messages')
                    || document.querySelector('main')
                    || document.body;
                anchor.insertBefore(banner, anchor.firstChild);
            }

            banner.innerHTML = '<div class="message-error error message"><div>'
                + escapeHtml(errorMessage)
                + '</div></div>';
            window.scrollTo(0, 0);
        },
        _handleError: function (errorMessage) {
            $(document).ready(function () {
                setTimeout(function () {
                    $('.message.error').fadeOut('slow');
                }, 10000000);

                var notNeedUpdateCount = 0;
                var needUpdateCheck = setInterval(() => {
                    if (notNeedUpdateCount === 40) { // ~10s
                        clearInterval(needUpdateCheck);
                    }
                    let messages = customerData.get('messages');
                    let newMessages = [];
                    let needUpdate = true;
                    $.each(messages().messages, function (key, value) {
                        if (value.type === 'error' && value.text === errorMessage) {
                            needUpdate = false;
                            return false;
                        }
                        newMessages.push({
                            type: value.type,
                            text: value.text
                        });
                    });

                    if (needUpdate) {
                        newMessages.push({
                            type: 'error',
                            text: $t(errorMessage)
                        });
                        customerData.set('messages', {messages: newMessages});
                    } else {
                        notNeedUpdateCount++;
                    }
                }, 250);

                $.localStorage.remove('express-error-message');
            });
        }
    });
});
