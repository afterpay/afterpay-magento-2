/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
var config = {
    map: {
        '*': {
            afterpay:    'https://www-dev.secure-afterpay.com.au/afterpay.js', // @todo change to use dynamic js window.checkoutConfig.payment.afterpay.afterpayJs
            transparent: 'Magento_Payment/transparent'
        }
    }
};
