define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Afterpay_Afterpay/js/action/create-afterpay-checkout',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/section-config'
], function (
    $,
    Component,
    additionalValidators,
    createAfterpayCheckoutAction,
    setPaymentInformationAction,
    errorProcessor,
    customerData,
    sectionConfig
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Afterpay_Afterpay/payment/afterpay'
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
                        $.mage.redirect(response.afterpay_redirect_checkout_url);
                    }).always(function () {
                        self.isPlaceOrderActionAllowed(true);
                    });

                }).fail(function (response) {
                    errorProcessor.process(response, self.messageContainer);
                }).always(function () {
                    self.isPlaceOrderActionAllowed(true);
                });
            }
        }
    });
});
