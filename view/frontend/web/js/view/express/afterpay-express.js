/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
define(
    [
        "jquery",
        'mage/url',
        'Magento_Customer/js/customer-data'
    ],
    function ( $, mageUrl,customerData) {
        var expressCheckout= {};
        return function (config) {

            // Afterpay Express Checkout
            function initAfterpayExpress() {
                var afterpayData = config.afterpayConfig;

                //CountryCode Object to pass in initialize function.
                var countryCurrencyMapping ={AUD:"AU", NZD:"NZ", USD:"US",CAD:"CA"};
                var countryCode = (afterpayData.baseCurrencyCode in countryCurrencyMapping)? countryCurrencyMapping[afterpayData.baseCurrencyCode]:'';

                if( $(".express-button").length && countryCode!=""){
                    AfterPay.initializeForPopup({
                        countryCode: countryCode,
                        buyNow: true,
                        shippingOptionRequired: true,
                        target: '.express-button',
                        onCommenceCheckout: function(actions){

                            if(expressCheckout.buttonType != 'undefined' && expressCheckout.buttonType == 'product-page'){
                                if(isFormValid()) {
                                    if(config.productType != "bundle"){
                                        $("#product_addtocart_form").submit();
                                    }
                                    AfterPay.shippingOptionRequired = Boolean(!config.isProductVirtual);

                                    setTimeout(function () {
                                        $.ajax({
                                            url: mageUrl.build("afterpay/payment/express") + '?action=start',
                                            success: function (data) {
                                                if (!data.success) {
                                                    actions.reject(AfterPay.constants.SERVICE_UNAVAILABLE);
                                                } else {
                                                    actions.resolve(data.token);
                                                }
                                            }
                                        });
                                    }, 5000);
                                }else {
                                    AfterPay.close();
                                    actions.reject(AfterPay.constants.SERVICE_UNAVAILABLE);

                                }
                            }else{
                                if(expressCheckout.buttonType!="undefined"){
                                    switch(expressCheckout.buttonType) {
                                        case "cart":
                                            AfterPay.shippingOptionRequired = Boolean(!config.isCartVirtual);
                                            break;
                                        case "mini-cart":
                                            AfterPay.shippingOptionRequired = isShippingRequired();
                                            break;
                                    }
                                }

                                $.ajax({
                                    url: mageUrl.build("afterpay/payment/express") + '?action=start',
                                    success: function (data) {
                                        if (!data.success) {
                                            actions.reject(AfterPay.constants.SERVICE_UNAVAILABLE);
                                        } else {
                                            actions.resolve(data.token);
                                        }
                                    }
                                });
                            }

                        },
                        onShippingAddressChange: function (shippingData, actions) {
                            $.ajax({
                                url: mageUrl.build("afterpay/payment/express")+'?action=change',
                                method: 'POST',
                                data: shippingData,
                                success: function(options){
                                    if (options.hasOwnProperty('error')) {
                                        actions.reject(AfterPay.constants.SERVICE_UNAVAILABLE);
                                    } else {
                                        actions.resolve(options.shippingOptions);
                                    }
                                }
                            });

                        },
                        onComplete: function (orderData) {
                            if (orderData.data.status == 'SUCCESS') {
                                $.ajax({
                                    url: mageUrl.build("afterpay/payment/express")+'?action=confirm',
                                    method: 'POST',
                                    data: orderData.data,
                                    beforeSend: function(){
                                        $("body").trigger('processStart');
                                    },
                                    success: function(result){
                                        if (result.success) {
                                            //To Clear mini-cart
                                            var sections = ['cart'];
                                            customerData.invalidate(sections);
                                            customerData.reload(sections, true);
                                            window.location.href = mageUrl.build("checkout/onepage/success");
                                        }
                                        else if(expressCheckout.buttonType != 'undefined' && expressCheckout.buttonType == 'product-page' && isFormValid()){
                                            window.location.href = window.checkout.shoppingCartUrl;
                                        }
                                    },
                                    complete: function(){
                                        $("body").trigger('processStop');
                                    }
                                });
                            }
                            else if(expressCheckout.buttonType != 'undefined' && expressCheckout.buttonType == 'product-page' && isFormValid()){
                                window.location.href = window.checkout.shoppingCartUrl;
                                $("body").trigger('processStop');
                            }
                        },
                        pickup: false,
                    });

                }
            }
            //Validate Product form
            function isFormValid(){
                if (!$('#product_addtocart_form').valid()) {
                    return false;
                }
                return true;
            }
            //Get isShippingRequired for mini-cart
            function isShippingRequired(){
                var isShippingRequired = false;
                var cartItems = customerData.get('cart')().items;
                if(cartItems && cartItems.length > 0){
                    $.each(cartItems,function(key,val){
                        if(!val.is_virtual){
                            isShippingRequired = true;
                            return false;
                        }
                    });
                }
                return isShippingRequired;
            }

            $(document).ready(function () {

                $(".express-button").on('click', function() {
                    if ($(this).is('[data-afterpay-entry-point]') && typeof AfterPay != 'undefined') {
                        expressCheckout.buttonType = $(this).data('afterpay-entry-point');
                    } else {
                        expressCheckout.buttonType = 'product-page'; // set a default button type
                    }
                });
                initAfterpayExpress();
            });
        }
    });
