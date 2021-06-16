/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
require(
    [
        "jquery",
        "Magento_Checkout/js/model/quote"
    ],
    function ( $, quote) {

        $(".cart-totals").bind("DOMSubtreeModified", function() {
            var totals = quote.getTotals()();
            const epsilon = Number.EPSILON ||  Math.pow(2, -52);
            $('afterpay-placement').attr('data-amount',(Math.round((parseFloat(totals['base_grand_total']) + epsilon) * 100) / 100).toFixed(2));

        });

    });
