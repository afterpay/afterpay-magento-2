define([
    'jquery'
], function ($) {
    const TARGET_BUTTON_SELECTOR = '.afterpay-express-button';
    const ENTRY_POINT_ATTRIBUTE = 'afterpay-entry-point';

    return {
        "handlerNames": {
            "validation": "validationHandler",
            "commenceCheckout": "onCommenceCheckout",
            "shippingAddressChange": "onShippingAddressChange",
            "complete": "onComplete",
        },
        "cart": {},
        "product-page": {},
        "mini-cart": {},
        initAfterpayPopup: function(countryCode) {
            this._initEntryPointTracking();
            this._initValidation();
            AfterPay.initializeForPopup({
                countryCode: countryCode,
                buyNow: true,
                shippingOptionRequired: true,
                pickup: false,
                target: TARGET_BUTTON_SELECTOR,
                onCommenceCheckout: this._getHandler(this.handlerNames.commenceCheckout),
                onShippingAddressChange: this._getHandler(this.handlerNames.shippingAddressChange),
                onComplete: this._getHandler(this.handlerNames.complete)
            });
        },
        setHandler: function (entryPoint, handlerName, handler) {
            this[entryPoint][handlerName] = handler;
        },
        _initValidation: function () {
            const self = this;
            $(TARGET_BUTTON_SELECTOR).click(function (e) {
                const clickedEntryPoint = $(this).data(ENTRY_POINT_ATTRIBUTE);
                if (clickedEntryPoint) {
                    const currentEntryPointsHandlers = self[clickedEntryPoint];
                    if (currentEntryPointsHandlers && currentEntryPointsHandlers[self.handlerNames.validation]) {
                        const validationHandler = currentEntryPointsHandlers[self.handlerNames.validation];
                        if (!validationHandler()) {
                            e.stopImmediatePropagation();
                        }
                    }
                }
            });
        },
        _initEntryPointTracking: function () {
            const self = this;
            $(TARGET_BUTTON_SELECTOR).click(function () {
                const clickedEntryPoint = $(this).data(ENTRY_POINT_ATTRIBUTE);
                if (clickedEntryPoint) {
                    self.currentEntryPoint = clickedEntryPoint;
                } else {
                    self.currentEntryPoint = '';
                }
            });
        },
        _getHandler: function (handlerName) {
            return (...args) => {
                const currentEntryPointsHandlers = this[this.currentEntryPoint];
                if (currentEntryPointsHandlers && currentEntryPointsHandlers[handlerName]) {
                    currentEntryPointsHandlers[handlerName](...args);
                }
            };
        }
    };
});
