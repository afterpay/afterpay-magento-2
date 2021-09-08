/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
/*browser:true*/
/*global define*/
define(['jquery'],
 function($) {
    'use strict';
    return  {
        redirectToAfterpay: function (data) {
          AfterPay.redirect({
				token: data.token
			});
        }
    }

});