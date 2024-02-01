define([
    'uiComponent',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/checkout-data',
    'mage/translate'
], function (
    Component,
    $,
    quote,
    priceUtils,
    totals,
    checkoutData
) {
    'use strict';

    return Component.extend({
        initWidget: function () {
            let widgetAmount = this._getWidgetAmount(totals.totals());

            window.afterpayWidget = new Square.Marketplace.SquarePlacement();
            afterpayWidget.mpid = window.checkoutConfig.payment.afterpay.mpid;
            afterpayWidget.pageType = 'checkout';
            afterpayWidget.amount = widgetAmount.amount;
            afterpayWidget.currency = widgetAmount.currency;
            afterpayWidget.type = 'payment-schedule';
            afterpayWidget.platform = 'Magento';

            document.getElementById('afterpay-widget-container').appendChild(afterpayWidget);

            totals.totals.subscribe((totals) => {
                if (afterpayWidget) {
                    afterpayWidget.setAttribute('data-amount', this._getWidgetAmount(totals).amount);
                }
            });

            if (checkoutData.getSelectedPaymentMethod() == 'afterpay'
                && checkoutConfig.payment.afterpay.isCBTCurrency === true) {
                this._hideBaseCurrencyChargeInfo();
            }
            quote.paymentMethod.subscribe(function (value) {
                if (value && value.method == 'afterpay' && checkoutConfig.payment.afterpay.isCBTCurrency === true) {
                    this._hideBaseCurrencyChargeInfo();
                } else {
                    this._showBaseCurrencyChargeInfo();
                }
            }, this);
        },
        _getWidgetAmount: function (totals) {
            let amount = window.checkoutConfig.payment.afterpay.isCBTCurrency
                ? totals.grand_total + totals.tax_amount
                : totals.base_grand_total;
            let currency = window.checkoutConfig.payment.afterpay.isCBTCurrency
                ? totals.quote_currency_code
                : totals.base_currency_code;

            return {
                amount: parseFloat(amount).toFixed(2),
                currency: currency
            }
        },
        _hideBaseCurrencyChargeInfo: function () {
            $('.opc-block-summary .totals.charge').hide();
        },
        _showBaseCurrencyChargeInfo: function () {
            $('.opc-block-summary .totals.charge').show();
        },
    });
});
