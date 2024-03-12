define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Afterpay_Afterpay/js/action/create-afterpay-checkout',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/section-config',
    'Magento_Checkout/js/model/quote'
], function (
    $,
    Component,
    additionalValidators,
    createAfterpayCheckoutAction,
    setPaymentInformationAction,
    errorProcessor,
    customerData,
    sectionConfig,
    quote
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Afterpay_Afterpay/payment/afterpay'
        },
        getCurrency: function () {

            let currency = window.checkoutConfig.payment.afterpay.isCBTCurrency
                ? quote.totals().quote_currency_code
                : quote.totals().base_currency_code;
            return currency;

        },
        getMPID: function () {
            return window.checkoutConfig.payment.afterpay.mpid;
        },
        getAmount: function() {
            let amount = window.checkoutConfig.payment.afterpay.isCBTCurrency
                ? quote.totals().grand_total : quote.totals().base_grand_total;

            return amount;
        },
        continueToAfterpay: function (data, event) {
            const self = this;

            if (event) {
                event.preventDefault();
            }

            if (additionalValidators.validate() && this.isPlaceOrderActionAllowed() === true) {
                this.isPlaceOrderActionAllowed(false);

                setPaymentInformationAction(
                    self.messageContainer,
                    self.getData()
                ).done(function () {

                    const captureUrlPath = 'afterpay/payment/capture';
                    createAfterpayCheckoutAction(self.messageContainer, {
                        confirmPath: captureUrlPath,
                        cancelPath: captureUrlPath
                    }).done(function (response) {

                        const sections = sectionConfig.getAffectedSections(captureUrlPath);
                        customerData.invalidate(sections);

                        let checkoutRedirectUrl=self._getCheckoutUrl(response.afterpay_redirect_checkout_url);

                        $.mage.redirect(checkoutRedirectUrl);

                    }).always(function () {
                        self.isPlaceOrderActionAllowed(true);
                    });

                }).fail(function (response) {
                    errorProcessor.process(response, self.messageContainer);
                }).always(function () {
                    self.isPlaceOrderActionAllowed(true);
                });
            }
        },
        _getCheckoutUrl: function (checkoutUrl) {

            let deviceData=$.mage.cookies.get("apt_pixel");
            let args="";
            // Append params from the cookie
            if (deviceData !== undefined && deviceData !=null && deviceData.length>0) {

                let queryParams=checkoutUrl.split("?")[1];
                const searchParams = new URLSearchParams(queryParams);

                let device=JSON.parse(atob(deviceData));
                if (device.hasOwnProperty('deviceId') &&
                    (/^[0-9a-z-]*$/i).test(device.deviceId) &&
                    searchParams.has('device_id')===false) {
                    args="&device_id="+device.deviceId;
                }

                if (device.hasOwnProperty('checkout') ) {
                    for (var prop in device.checkout){
                        let val=device.checkout[prop];
                        if ((/^[0-9a-z]+$/i).test(prop) && (/^[0-9a-z-]*$/i).test(val) && searchParams.has(prop)===false) {
                            args+="&"+prop+"="+val;
                        }
                    }
                }
            }
            return checkoutUrl+args;
        },
    });
});
