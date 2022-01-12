define([
    'Afterpay_Afterpay/js/service/container/cart/abstract-data-retriever',
    'Magento_Customer/js/customer-data',
], function (AbstractDataRetriever, customerData) {
    return AbstractDataRetriever.extend({
        defaults: {
            isVisible: false,
            dataAmount: "0",
            modelWithPrice: customerData.get('cart'),
            priceKey: "subtotalAmount"
        },
    });
});

