/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messageList'
    ],
    function ($, Component, quote, additionalValidators, globalMessageList) {
        'use strict';

        return Component.extend({
            /** Don't redirect to the success page immediately after placing order **/
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Afterpay_Afterpay/payment/afterpaypayovertime',
                billingAgreement: ''
            },

            /** Returns payment acceptance mark image path */
            getAfterpayPayovertimeLogoSrc: function() {
                return 'https://www.afterpay.com.au/wp-content/themes/afterpay/assets/img/logo_scroll.png';
            },

            /**
             * Terms and condition link
             * @returns {*}
             */
            getTermsConditionUrl: function() {
                return window.checkoutConfig.payment.afterpay.termsConditionUrl;
            },

            /**
             * Get Grand Total of the current cart
             * @returns {*}
             */
            getGrandTotal: function() {
                var total = window.checkoutConfig.totalsData.grand_total;
                var format = window.checkoutConfig.priceFormat.pattern;
                return format.replace(/%s/g, total.toFixed(window.checkoutConfig.priceFormat.precision));
            },

            /**
             * Returns the installment fee of the payment */
            getAfterpayInstallmentFee: function() {
                // Checking and making sure checkoutConfig data exist and not total 0 dollar
                if (typeof window.checkoutConfig !== 'undefined' &&
                    window.checkoutConfig.totalsData.grand_total > 0) {

                    // Set installment fee from grand total and check format price to be output
                    var installmentFee = window.checkoutConfig.totalsData.grand_total / 4;
                    var format = window.checkoutConfig.priceFormat.pattern;

                    // return with the currency code ($) and decimal setting (default: 2)
                    return format.replace(/%s/g, installmentFee.toFixed(window.checkoutConfig.priceFormat.precision));
                }
            },

            /**
             *  process Afterpay Payment
             */
            continueAfterpayPayment: function () {
                // Added additional validation to check
                if (additionalValidators.validate()) {
                    // start afterpay payment is here
                    var afterpay = window.checkoutConfig.payment.afterpay;

                    // Making sure it using current flow
                    if (afterpay.paymentAction == 'order') {
                        this.placeOrder();
                    }
                    else {
                        // Making sure it using API V1
                        var url = "/afterpay/payment/process";
                        var data = $("#co-shipping-form").serialize();

                        //handle guest and registering customer emails
                        if(!window.checkoutConfig.quoteData.customer_id){
                            var email = document.getElementById("customer-email").value;
                        }
                        else {
                            var email = window.checkoutConfig.customerData.email;
                        }
                        var data = data + '&email=' + email;


                        $.ajax({
                            url: url,
                            method:'post',
                            showLoader: true,
                            data: data,
                            success: function(response) {

                                var data = $.parseJSON(response);

                                if( data['success'] && (typeof data['token'] !== 'undefined' && data['token'] !== null && data['token'].length) ) {
                                    AfterPay.init();

                                    switch (window.Afterpay.checkoutMode) {
                                        case 'lightbox':
                                            AfterPay.display({
                                                token: data['token']
                                            });
                                            break;

                                        case 'redirect':
                                            AfterPay.redirect({
                                                token: data['token']
                                            });
                                            break;
                                    }
                                }
                                else if( typeof data['error'] !== 'undefined' &&  typeof data['message'] !== 'undefined' && 
                                        data['error'] && data['message'].length ) {
                                  
                                    globalMessageList.addErrorMessage({
                                        'message': data['message']
                                    });
                                }
                                else if( typeof data['token'] === 'undefined' || data['token'] === null || !data['token'].length ) {
                                    globalMessageList.addErrorMessage({
                                        'message': "Transaction generation error."
                                    });
                                }
                                else {
                                    globalMessageList.addErrorMessage({
                                        'message': data.message
                                    });
                                }
                            }
                        });
                    }
                }
            },

            /**
             * Start popup or redirect payment
             *
             * @param response
             */
            afterPlaceOrder: function() {
                
                // start afterpay payment is here
                var afterpay = window.checkoutConfig.payment.afterpay;

                // Making sure it using current flow
                var url = "/afterpay/payment/process";
                
                $.ajax({
                    url: url,
                    method:'post',
                    success: function(response) {

                        var data = $.parseJSON(response);

                        AfterPay.init({
                            relativeCallbackURL: window.checkoutConfig.payment.afterpay.afterpayReturnUrl
                        });

                        switch (window.Afterpay.checkoutMode) {
                            case 'lightbox':
                                AfterPay.display({
                                    token: data['token']
                                });
                                break;

                            case 'redirect':
                                AfterPay.redirect({
                                    token: data['token']
                                });
                                break;
                        }
                    }
                });
            }
        });
    }
);
