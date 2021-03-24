/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
require(
	[
		"jquery",
		"Magento_Catalog/js/price-utils",
		"Magento_Checkout/js/model/quote",
		'mage/url',
		'Magento_Customer/js/customer-data'
	],
	function ( $, priceUtils, quote,mageUrl,customerData) {
	// Afterpay Express Checkout 	
	 function initAfterpayExpress() {

			 var afterpayData = window.checkoutConfig.payment.afterpay;

			 //CountryCode Object to pass in initialize function.
	         var countryCurrencyMapping ={AUD:"AU", NZD:"NZ", USD:"US",CAD:"CA"};
	         var countryCode = (afterpayData.currencyCode in countryCurrencyMapping)? countryCurrencyMapping[afterpayData.currencyCode]:'';
			 var isShippingRequired= (!quote.isVirtual())?true:false;
		if( $("#afterpay-express-button").length && countryCode!=""){
			 AfterPay.initializeForPopup({
		            countryCode: countryCode, // fetch
		            shippingOptionRequired: isShippingRequired, //fetch for virtual type
		            buyNow: true,
		            target: '#afterpay-express-button',
		            onCommenceCheckout: function(actions){
		            	$.ajax({
		                    url: mageUrl.build("afterpay/payment/express")+'?action=start',
		                    success: function(data){
		                        if (!data.success) {
		                            actions.reject(data.message);
		                        } else {
		                            actions.resolve(data.token);
		                        }
		                    }
		                });
		            },
		            onShippingAddressChange: function (shippingData, actions) {
			            	$.ajax({
			                    url: mageUrl.build("afterpay/payment/express")+'?action=change',
			                    method: 'POST',
			                    data: shippingData,
			                    success: function(options){
			                    	if (options.hasOwnProperty('error')) {
			                             actions.reject(AfterPay.constants.SERVICE_UNAVAILABLE, options.message);
			                        } else {
			                            actions.resolve(options.shippingOptions);
			                        }
			                    }
			                });

		            },
		            onComplete: function (orderData) {
		            	$("body").trigger('processStart');
		            	 if (orderData.data.status == 'SUCCESS') {

			            	$.ajax({
			                    url: mageUrl.build("afterpay/payment/express")+'?action=confirm',
			                    method: 'POST',
			                    data: orderData.data,
			                    success: function(result){
			                    	$("body").trigger('processStop');
			                    	if (result.success) {
			                    		//To Clear mini-cart
			                    		var sections = ['cart'];
			                    		customerData.invalidate(sections);
			                    		customerData.reload(sections, true);

			                    		window.location.href = mageUrl.build("checkout/onepage/success");
			                    	}
			                    }
			                });
		            	 }
		            	 $("body").trigger('processStop');

		            },
		          pickup: false,
		        });

		 }
	 }

	 	$(document).ready(function() {
		 initAfterpayExpress();
		});

		$(".cart-totals").bind("DOMSubtreeModified", function() {
			var totals = quote.getTotals()();
			const epsilon = Number.EPSILON ||  Math.pow(2, -52);
			$('afterpay-placement').attr('data-amount',(Math.round((parseFloat(totals['base_grand_total']) + epsilon) * 100) / 100).toFixed(2));

		});

});
