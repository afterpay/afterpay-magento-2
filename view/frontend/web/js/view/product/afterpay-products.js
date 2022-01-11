/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        'Afterpay_Afterpay/js/view/express/button',
        'Magento_Customer/js/customer-data'
    ],
    function ($, expressBtn, customerData) {

        return function (config) {

            $(document).ready(function ($) {

                setFinalAmount();

                $('body').on('click change', $('form#product_addtocart_form'), function (e) {
                    setFinalAmount();
                });
                $('body').on('input', $('form#product_addtocart_form select'), function (e) {
                    setTimeout(function () {
                        $('form#product_addtocart_form').trigger('change');
                    }, 3);
                });


            });
            function setExpressCheckout(config, price, expressBtn)
            {
                if(config.isECenabled==1) {
                    if (config.afterpayConfig.paymentActive &&
                        price <= config.afterpayConfig.maxLimit &&
                        price >= config.afterpayConfig.minLimit &&
                        price > 0 &&
                        expressBtn.canDisplayOnPDP(config.isProductVirtual)
                    ) {
                        $("#afterpay-pdp-express-button").show();
                    } else {
                        $("#afterpay-pdp-express-button").hide();
                    }
                }
            }
            function setFinalAmount() {

                    let price_raw = $(".page-main [data-price-type=finalPrice]:first").text() || '';
                    if (!price_raw) price_raw = $('.page-main .product-info-price .price-final_price .price-wrapper:not([data-price-type="oldPrice"]) span.price:first').text();

                    // Show Express Checkout button

                    var newPrice=price_raw.match(/[\d\.\,]+/g);
                    var price =price_to_number(newPrice[0],config.productPriceFormat.decimalSymbol,config.productPriceFormat.groupSymbol);
                if(config.isDisplay==1) {
                    const epsilon = Number.EPSILON ||  Math.pow(2, -52);
                    $('afterpay-placement').attr('data-amount', (Math.round((parseFloat(price) + epsilon) * 100) / 100).toFixed(2));
                }
                var cart = customerData.get('cart');
                setExpressCheckout(config, price, expressBtn);
                cart.subscribe(function () {
                    setExpressCheckout(config, price, expressBtn);
                });
            }

            //Function to convert currency to number
            function price_to_number(amount,decimalSymbol,groupSymbol){
                if(!amount){return 0;}
                if(decimalSymbol=="," && groupSymbol=="."){
                    //Eg. 10.000.500,61 => price_to_number => 10000500.61
                    amount=amount.split('.').join('');
                    amount=amount.split(',').join('.');
                }else {
                    //Eg. 10,000,500.61 => price_to_number => 10000500.61
                    amount = amount.split(',').join('');
                }
                return Number(amount.replace(/[^0-9.]/g, ""));
            }
        };
    }
);
