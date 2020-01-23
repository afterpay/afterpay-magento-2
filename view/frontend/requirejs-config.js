/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
var config = {
    map: {
        '*': {
            afterpay:    'https://www-dev.secure-afterpay.com.au/afterpay.js', // @todo change to use dynamic js window.checkoutConfig.payment.afterpay.afterpayJs
            transparent: 'Magento_Payment/transparent'
        }
    }
};
