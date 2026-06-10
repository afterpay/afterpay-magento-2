define([
    'uiComponent',
    'Magento_Checkout/js/model/totals',
    'mage/translate'
], function (
    Component,
    totals,
    $t
) {
    'use strict';

    return Component.extend({
        getTermsText: function () {
            let afterpayTermsText = '';
            switch(totals.totals().quote_currency_code){
                case 'USD':
                case 'CAD':
                    afterpayTermsText = $t('You will be redirected to the Afterpay website to fill out your payment information. You will be redirected back to our site to complete your order.');
                    break;
                default:
                    afterpayTermsText = $t('You will be redirected to the Afterpay website when you proceed to checkout.');
            }

            return afterpayTermsText;
        },
        getTermsLink: function () {
            let afterpayCheckoutTermsLink = '';
            switch(totals.totals().quote_currency_code){
                case 'USD':
                    afterpayCheckoutTermsLink = "https://www.afterpay.com/en-US/installment-agreement";
                    break;
                case 'CAD':
                    afterpayCheckoutTermsLink = "https://www.afterpay.com/en-CA/instalment-agreement";
                    break;
                case 'NZD':
                    afterpayCheckoutTermsLink="https://www.afterpay.com/en-NZ/terms-of-service";
                    break ;
                case 'AUD':
                    afterpayCheckoutTermsLink="https://www.afterpay.com/en-AU/terms-of-service";
                    break ;
                default:
                    afterpayCheckoutTermsLink = "https://www.afterpay.com/terms/";
            }
            return afterpayCheckoutTermsLink;
        },
    });
});
