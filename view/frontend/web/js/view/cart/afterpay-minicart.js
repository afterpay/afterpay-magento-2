/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        "Magento_Catalog/js/price-utils",
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Afterpay_Afterpay/js/view/express/button'
    ],
    function ($, priceUtils, mageUrl, customerData, expressBtn) {
        return function (config){
            function canDispayExpress(){
                var minicart_subtotal = customerData.get('cart')().subtotalAmount;

                if (!isAfterpayRestricted() &&
                    parseFloat(minicart_subtotal) >= parseFloat(config.afterpayConfig.minLimit) &&
                    parseFloat(minicart_subtotal) <= parseFloat(config.afterpayConfig.maxLimit) &&
                    (config.afterpayConfig.paymentActive) &&
                    expressBtn.canDisplayOnMinicart()
                ) {
                    $('#afterpay-minicart-express-button').show();
                } else {
                    $('#afterpay-minicart-express-button').hide();
                }
            }


            function isAfterpayRestricted(){
                var cartItems = customerData.get('cart')().items;
                var afterpayRestricted = false;
                if(cartItems && cartItems.length > 0){
                    $.each(cartItems,function(key,val){
                        if(val.afterpay_restricted){
                            afterpayRestricted = true;
                            return false;
                        }
                    });
                }
                return afterpayRestricted;
            }
            $(document).ready(function() {
                $('[data-block=\'minicart\']').on('contentUpdated', function () {
                    canDispayExpress();
                });
            });
        }
    });
