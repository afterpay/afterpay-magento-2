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
			$('afterpay-placement').attr('data-amount',totals['base_grand_total']);
			
		});
	}
);