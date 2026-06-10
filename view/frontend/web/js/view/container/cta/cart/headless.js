(function (d, w, s) {
    let query = `mutation {
                    getAfterpayConfigCart(input: {
                        cart_id: "${afterpayCartId}"
                        store_id: "${afterpayStoreId}"
                    }) {
                        allowed_currencies
                        is_enabled
                        mpid
                        is_enabled_cta_cart_page_headless
                        show_lover_limit
                        is_product_allowed
                        is_cbt_enabled
                        placement_after_selector
                        price_selector
                        placement_id
                    }
                }`;

    let graphqlEndpoint = window.location.origin + '/graphql';
    let configData;
    let lastKnownPrice = null;

    function fetchConfigData() {
        const requestOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({query})
        };

        return fetch(graphqlEndpoint, requestOptions)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    const afterpayConfig = data.data.getAfterpayConfigCart;
                    let isEnabledCtaMinicart = afterpayConfig.is_enabled_cta_cart_page_headless;

                    if (afterpayConfig.is_enabled && isEnabledCtaMinicart) {
                        let dataMPID = afterpayConfig.mpid,
                            dataShowLowerLimit = afterpayConfig.show_lover_limit,
                            dataPlatform = 'Magento',
                            dataPageType = 'cart',
                            dataIsEligible = afterpayConfig.is_product_allowed,
                            dataCbtEnabledString = Boolean(afterpayConfig.is_cbt_enabled).toString(),
                            squarePlacementId = 'afterpay-cta-cart',
                            widgetContainer = afterpayConfig.placement_after_selector,
                            priceWrapper = afterpayConfig.price_selector,
                            placementId = afterpayConfig.placement_id;

                        return {
                            dataShowLowerLimit: dataShowLowerLimit,
                            dataCurrency: afterpayCurrency,
                            dataIsEligible: dataIsEligible,
                            dataMPID: dataMPID,
                            dataCbtEnabledString: dataCbtEnabledString,
                            dataPlatform: dataPlatform,
                            dataPageType: dataPageType,
                            widgetContainer: widgetContainer,
                            squarePlacementId: squarePlacementId,
                            priceWrapper: priceWrapper,
                            placementId: placementId
                        };
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            })
            .catch(error => {
                console.error("Error:", error);
                throw error;
            });
    }

    function processAfterpay() {
        if (configData && configData.priceWrapper) {
            const priceElement = document.querySelector(configData.priceWrapper);
            if (priceElement) {
                updateWidgetInstance();
                lastKnownPrice = getPriceWithoutCurrency(configData.priceWrapper);
                setInterval(checkCartUpdated, 1000);
                setInterval(checkPlacementExists, 1000);
            } else {
                if (typeof window.waitForSelector === 'function') {
                    window.waitForSelector(configData.priceWrapper)
                        .then(() => {
                            updateWidgetInstance();
                            lastKnownPrice = getPriceWithoutCurrency(configData.priceWrapper);
                            setInterval(checkCartUpdated, 1000);
                            setInterval(checkPlacementExists, 1000);
                        });
                }
            }
        }
    }

    function checkPlacementExists() {
        if (!configData || !configData.squarePlacementId) {
            return;
        }
        if (!document.getElementById(configData.squarePlacementId)) {
            updateWidgetInstance();
        }
    }

    function updateWidgetInstance() {
        const priceWrapper = configData.priceWrapper;
        const priceSelectorElement = document.querySelector(priceWrapper);

        if (!priceSelectorElement) {
            return;
        }

        const squarePlacementSelector = document.getElementById(configData.squarePlacementId);
        if (squarePlacementSelector) {
            squarePlacementSelector.outerHTML = ""; // Remove old widget and add a new one
        }

        let priceAmount = getPriceWithoutCurrency(priceWrapper);

        updateAfterpayAmount(priceAmount);
    }

    function checkCartUpdated() {
        const currentPrice = getPriceWithoutCurrency(configData.priceWrapper);
        if (currentPrice && currentPrice !== lastKnownPrice) {
            lastKnownPrice = currentPrice;
            updateWidgetInstance();
        }
    }

    // Get price without currency symbol
    function getPriceWithoutCurrency(selector) {
        const element = document.querySelector(selector);

        if (element) {
            let priceText = element.innerText.trim(),
                price = priceText.replace(/[^\d.]/g, '');
            return parseFloat(price);
        } else {
            return null;
        }
    }

    // Add the widget
    function updateAfterpayAmount(amount) {
        let wrapperHtml = document.querySelector(configData.widgetContainer),
            dataCurrency = configData?.dataCurrency ? configData.dataCurrency : window.afterpayCurrency;

        if (!wrapperHtml && configData.widgetContainer) {
            if (typeof window.waitForSelector === 'function') {
                window.waitForSelector(configData.widgetContainer)
                    .then((element) => {
                        updateHtml(element, amount, dataCurrency);
                    });
            } else {
                let interval = setInterval(() => {
                    wrapperHtml = document.querySelector(configData.widgetContainer);
                    if (wrapperHtml) {
                        clearInterval(interval);
                        updateHtml(wrapperHtml, amount, dataCurrency);
                    }
                }, 1000);
            }
        } else {
            updateHtml(wrapperHtml, amount, dataCurrency);
        }
    }

    function updateHtml(wrapperHtml, amount, dataCurrency) {
        const blockHtml = '<square-placement id="' + configData.squarePlacementId + '"' +
            'data-show-lower-limit="' + configData.dataShowLowerLimit + '"' +
            'data-currency="' + dataCurrency + '"' +
            'data-is-eligible="' + configData.dataIsEligible + '"' +
            'data-amount="' + amount + '"' +
            'data-mpid="' + configData.dataMPID + '"' +
            'data-cbt-enabled="' + configData.dataCbtEnabledString + '"' +
            'data-platform="' + configData.dataPlatform + '"' +
            'data-page-type="' + configData.dataPageType + '"' +
            'data-placement-id="' + configData.placementId + '"></square-placement>';

        if (wrapperHtml) {
            wrapperHtml.insertAdjacentHTML('afterend', blockHtml);
        }
    }

    window.addEventListener("load", (event) => {
        if (afterpayCartId !== "") {
            fetchConfigData()
                .then(theConfig => {
                    configData = theConfig;
                    if (configData && configData.priceWrapper) {
                        processAfterpay();
                    }
                })
                .catch(error => console.error("Error: ", error));
        }
    });

})(document, window, 'script');
