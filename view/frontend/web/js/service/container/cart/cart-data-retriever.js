define([
    'Afterpay_Afterpay/js/service/container/cart/abstract-data-retriever',
    'Magento_Checkout/js/model/totals'
], function (AbstractDataRetriever, totalsModel) {
    return AbstractDataRetriever.extend({
        defaults: {
            isVisible: false,
            dataAmount: "0",
            modelWithPrice: totalsModel.totals,
            priceKey: (checkoutConfig.payment.afterpay.isCBTCurrency === true ) ? "grand_total" : "base_grand_total"
        },
    });
});
