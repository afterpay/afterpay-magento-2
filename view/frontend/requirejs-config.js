var config = {
    map: {
        '*': {
            "afterpayBaseContainer": "Afterpay_Afterpay/js/view/container/container",
            "afterpayCta": "Afterpay_Afterpay/js/view/container/cta/cta",
            "afterpayExpressCheckoutButton": "Afterpay_Afterpay/js/view/container/express-checkout/button",
            "afterpayExpressCheckoutButtonPdp": "Afterpay_Afterpay/js/view/container/express-checkout/product/button"
        }
    },
    config: {
        mixins: {
            "Magento_Catalog/js/price-box": {
                "Afterpay_Afterpay/js/service/container/pricebox-widget-mixin": true
            },
            "Magento_ConfigurableProduct/js/configurable": {
                "Afterpay_Afterpay/js/service/container/configurable-mixin": true
            }
        }
    }
};
