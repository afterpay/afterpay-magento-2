/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 * Updated on 27th March 2018
 * Removed API V0 functionality
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messageList',
        'Magento_Customer/js/customer-data',
        'Magento_Customer/js/section-config'
    ],
    function ($, Component, quote, resourceUrlManager, storage, mageUrl, additionalValidators, globalMessageList, customerData, sectionConfig) {
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

                var total = quote.getCalculatedTotal();
                var format = window.checkoutConfig.priceFormat.pattern

                storage.get(resourceUrlManager.getUrlForCartTotals(quote), false)
                .done(
                    function (response) {

                        var amount = response.base_grand_total;
                        var installmentFee = response.base_grand_total / 4;
                        var installmentFeeLast = amount - installmentFee.toFixed(window.checkoutConfig.priceFormat.precision) * 3;

                        $(".afterpay_total_amount").text( format.replace(/%s/g, amount.toFixed(window.checkoutConfig.priceFormat.precision)) );
                        $(".afterpay_instalments_amount").text( format.replace(/%s/g, installmentFee.toFixed(window.checkoutConfig.priceFormat.precision)) );
                        $(".afterpay_instalments_amount_last").text( format.replace(/%s/g, installmentFeeLast.toFixed(window.checkoutConfig.priceFormat.precision)) );

                        return format.replace(/%s/g, amount);
                    })
                .fail(
                   function (response) {
                       //do your error handling

                    return 'Error';
                });
            },

            /**
             * Get Checkout Message based on the currency
             * @returns {*}
             */
            getCheckoutText: function() {

                var afterpay = window.checkoutConfig.payment.afterpay;
                var afterpayCheckoutText = '';
                if(afterpay.currencyCode == 'AUD')
                {
                    afterpayCheckoutText = 'Four interest-free payments totalling';
                } else if(afterpay.currencyCode == 'NZD')
                {
                    afterpayCheckoutText = 'Four interest-free payments totalling';
                } else if(afterpay.currencyCode == 'USD')
                {
                    afterpayCheckoutText = 'Four interest-free installments totalling';
                }
                
                return afterpayCheckoutText;
            },

            getOptionalTermsText: function() {

                var afterpay = window.checkoutConfig.payment.afterpay;
                var afterpayCheckoutTermsText = '';
                if(afterpay.currencyCode == 'USD')
                {
                    return -1;
                }
                
                return 1;
            },

            /**
             * Returns the installment fee of the payment */
            getAfterpayInstallmentFee: function() {
                // Checking and making sure checkoutConfig data exist and not total 0 dollar
                if (typeof window.checkoutConfig !== 'undefined' &&
                    quote.getCalculatedTotal() > 0) {

                    // Set installment fee from grand total and check format price to be output
                    var installmentFee = quote.getCalculatedTotal() / 4;
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
                    // Making sure it using API V1
                    var url = mageUrl.build("afterpay/payment/process");
                    var data = $("#co-shipping-form").serialize();
                    var email = window.checkoutConfig.customerData.email;
                    //CountryCode Object to pass in initialize function.
                    var countryCode = {};
                    if(afterpay.currencyCode == 'AUD')
                    {
                        countryCode = {countryCode: "AU"};
                    } else if(afterpay.currencyCode == 'NZD')
                    {
                        countryCode = {countryCode: "NZ"};
                    } else if(afterpay.currencyCode == 'USD')
                    {
                        countryCode = {countryCode: "US"};
                    }

                        //handle guest and registering customer emails
                        if (!window.checkoutConfig.quoteData.customer_id) {
                            email = document.getElementById("customer-email").value;
                        }

                        data = data + '&email=' + email;


                        $.ajax({
                            url: url,
                            method: 'post',
                            data: data,
                            beforeSend: function () {
                                $('body').trigger('processStart');
                            }
                        }).done(function (response) {
                            // var data = $.parseJSON(response);
                            var data = response;

                            if( data.success && (typeof data.token !== 'undefined' && data.token !== null && data.token.length) ) {
                                    
                                //Init or Initialize Afterpay
                                //Pass countryCode to Initialize function
                                if (typeof AfterPay.initialize === "function") {
                                    AfterPay.initialize(countryCode);
                                }
                                else {
                                    AfterPay.init();
                                }

                                //Waiting for all AJAX calls to resolve to avoid error messages upon redirection
                                $("body").ajaxStop(function() {
                                    switch (window.Afterpay.checkoutMode) {
                                        case 'lightbox':
                                            AfterPay.display({
                                                token: data.token
                                            });
                                            break;

                                        case 'redirect':
                                            AfterPay.redirect({
                                                token: data.token
                                            });
                                            break;
                                    }
                                });

                            } else if (typeof data.error !== 'undefined' && typeof data.message !== 'undefined' &&
                                data.error && data.message.length) {

                                globalMessageList.addErrorMessage({
                                    'message': data.message
                                });
                            } 
                            else {
                                globalMessageList.addErrorMessage({
                                    'message': data.message
                                });
                            }
                        }).fail(function () {
                            window.location.reload();
                        }).always(function () {
                            customerData.invalidate(['cart']);
                            $('body').trigger('processStop');
                        });

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
                var url = mageUrl.build("afterpay/payment/process");
                
                $.ajax({
                    url: url,
                    method:'post',
                    success: function(response) {

                        // var data = $.parseJSON(response);
                        var data = response;

                        if (typeof AfterPay.initialize === "function") { 
                            AfterPay.initialize({
                                relativeCallbackURL: window.checkoutConfig.payment.afterpay.afterpayReturnUrl
                            });
                        } else {
                            AfterPay.init({
                                relativeCallbackURL: window.checkoutConfig.payment.afterpay.afterpayReturnUrl
                            });
                        }

                        switch (window.Afterpay.checkoutMode) {
                            case 'lightbox':
                                AfterPay.display({
                                    token: data.token
                                });
                                break;

                            case 'redirect':
                                AfterPay.redirect({
                                    token: data.token
                                });
                                break;
                        }
                    }
                });
            }
        });
    }
);
