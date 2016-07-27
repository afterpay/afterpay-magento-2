/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
/*jshint jquery:true*/
define([
    "jquery",
    "jquery/ui",
], function ($) {
    'use strict';

    $.widget('mage.braintreeDataJs', {
        options: {
            kountId: false
        },
        _create: function () {
            var self = this;
            var formId = self.options.formId;
            var defaultPaymentFormId='co-payment-form';
            if (!$('#' + formId)) {
                formId = defaultPaymentFormId;
            }
            window.onBraintreeDataLoad = function () {

                var env;
                if (self.options.kountId) {
                    env = BraintreeData.environments.production.withId(self.options.kountId);
                } else {
                    env = BraintreeData.environments.production;
                }

                BraintreeData.setup(self.options.merchantId, formId, env);
            };

            if (formId != defaultPaymentFormId) {
                $.getScript(self.options.braintreeDataJs);
            }
        }
    });
    return $.mage.braintreeDataJs;
});
