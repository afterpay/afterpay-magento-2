/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
require(
	[
		"jquery",
		"Magento_Catalog/js/price-utils"
	],
	function ( $, priceUtils, quote ) {

		var afterpay_instalment_element = $('.afterpay-installments.afterpay-installments-amount');

		var max_limit = afterpay_instalment_element.attr('maxLimit');
		var min_limit = afterpay_instalment_element.attr('minLimit');
		var product_type = afterpay_instalment_element.attr('product_type');
 
		$(document).ready(function($) {
			setInstalment(afterpay_instalment_element, max_limit, min_limit);

			$('body').on('click change', $('form#product_addtocart_form'), function (e) {
				setInstalment(afterpay_instalment_element, max_limit, min_limit);
			});
			$('body').on('input', $('form#product_addtocart_form select'), function (e) {
				setTimeout(function() {
					$('form#product_addtocart_form').trigger('change');
				}, 3);
			});
		});

		function setInstalment(afterpay_instalment_element, max_limit, min_limit)
		{
			//var price_raw = $('span.price-final_price > span.price-wrapper > span.price:first');
			//Above line only extracts the value from first price element product page. This might cause problem in some cases
			if(product_type=="bundle" && $("[data-price-type=minPrice]:first").text()!=""){
				var price_raw = $("[data-price-type=minPrice]:first");
			}
			else{
				var price_raw = $("[data-price-type=finalPrice]:first");
			}
			

			var price = price_raw.text().match(/[\d\.]+/g);

			if (price) {
				if (price[1]) {
					product_variant_price = price[0]+price[1];
				} else {
					product_variant_price = price[0];
				}

				var instalment_price = parseFloat(Math.round(product_variant_price / 4 * 100) / 100);

				//pass the price format object - fix for the group product format

				var format = {decimalSymbol: '.',pattern:'$%s'};
				var formatted_instalment_price = priceUtils.formatPrice(instalment_price,format);

				$('.afterpay-installments.afterpay-installments-amount .afterpay_instalment_price').text(formatted_instalment_price);

				if (parseFloat(product_variant_price) >= parseFloat(min_limit) && parseFloat(product_variant_price) <= parseFloat(max_limit)) {
					afterpay_instalment_element.show();
				} else {
					afterpay_instalment_element.hide();
				}
			}
		}
	}
);
