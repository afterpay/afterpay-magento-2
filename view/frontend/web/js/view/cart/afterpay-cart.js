/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
require(
	[
		"jquery",
		"Magento_Catalog/js/price-utils",
		"Magento_Checkout/js/model/quote"
	],
	function ( $, priceUtils, quote ) {
		
		$(".cart-totals").bind("DOMSubtreeModified", function() {
			var totals = quote.getTotals()();
			var instalment_price = parseFloat(Math.round(totals['base_grand_total'] / 4 * 100) / 100);
			var format = {decimalSymbol: '.',pattern:'$%s'};
			var formatted_instalment_price = priceUtils.formatPrice(instalment_price,format);
			$('.payment-method-note.afterpay-checkout-note .afterpay_instalment_price').text(formatted_instalment_price);
		});
	}
);