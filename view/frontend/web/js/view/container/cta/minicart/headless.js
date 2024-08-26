(function (d, w, s) {
    let graphqlEndpoint = window.location.origin + '/graphql';
    let configData,
        cartId = '',
        storeId = '';
    let lastKnownPrice = null;

    function fetchConfigData() {
        const query = `mutation {
             getAfterpayConfigMiniCart(input: {
                 cart_id: "${cartId}"
                 store_id: "${storeId}"
             }) {
                 allowed_currencies
                 is_enabled
                 mpid
                 is_enabled_cta_minicart_headless
                 show_lover_limit
                 is_product_allowed
                 is_cbt_enabled
                 placement_wrapper
                 placement_after_selector
                 price_selector
             }
         }`;

        const requestOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                query,
                variables: {cartId, storeId},
            })
        };

        return fetch(graphqlEndpoint, requestOptions)
            .then(response => response.json())
            .then(data => {
                if (data && !data.errors) {
                    const afterpayConfig = data.data.getAfterpayConfigMiniCart;
                    let isEnabledCtaMinicart = afterpayConfig.is_enabled_cta_minicart_headless;

                    if (afterpayConfig.is_enabled && isEnabledCtaMinicart) {
                        let dataMPID = afterpayConfig.mpid,
                            dataShowLowerLimit = afterpayConfig.show_lover_limit,
                            dataPlatform = 'Magento',
                            dataPageType = 'mini-cart',
                            dataIsEligible = afterpayConfig.is_product_allowed,
                            dataCbtEnabledString = Boolean(afterpayConfig.is_cbt_enabled).toString(),
                            squarePlacementId = 'afterpay-cta-mini-cart',
                            minicartBodyWidgetContainer = afterpayConfig.placement_wrapper,
                            widgetContainer = afterpayConfig.placement_after_selector,
                            priceWrapper = afterpayConfig.price_selector;

                        return {
                            dataShowLowerLimit: dataShowLowerLimit,
                            dataCurrency: afterpayCurrency,
                            dataLocale: afterpayLocale,
                            dataIsEligible: dataIsEligible,
                            dataMPID: dataMPID,
                            dataCbtEnabledString: dataCbtEnabledString,
                            dataPlatform: dataPlatform,
                            dataPageType: dataPageType,
                            minicartBodyWidgetContainer: minicartBodyWidgetContainer,
                            widgetContainer: widgetContainer,
                            squarePlacementId: squarePlacementId,
                            priceWrapper: priceWrapper
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
            updateWidgetInstance();
            lastKnownPrice = getPriceWithoutCurrency(configData.priceWrapper);
            setInterval(checkCartUpdated, 1000);
        }
    }

    function observeCacheStorageChanges() {
        const initialCacheStorageData = localStorage.getItem('mage-cache-storage');

        if (initialCacheStorageData) {
            const initialCacheStorage = JSON.parse(initialCacheStorageData);

            if (Object.keys(initialCacheStorage).length === 0) {
                waitForStorageData();
            } else {
                if (initialCacheStorage.cart) {
                    const initialStoreId = initialCacheStorage.cart.storeId;
                    let initialCartId = initialCacheStorage.cart.cartId;

                    if ((initialCartId !== '') && (initialStoreId !== '')) {
                        cartId = initialCartId;
                        storeId = initialStoreId;
                        callFetchConfigData();
                    } else {
                        waitForStorageData();
                    }
                } else {
                    waitForStorageData();
                }
            }
        } else {
            waitForStorageData();
        }
    }

    function waitForStorageData() {
        let interval = setInterval(function () {
            let result = observeLocalStorageEmptyCartChanges();
            if (result) {
                clearInterval(interval);
            }
        }, 1000);
    }

    function observeLocalStorageEmptyCartChanges() {
        const updatedCacheStorageData = localStorage.getItem('mage-cache-storage');

        if (updatedCacheStorageData) {
            const updatedCacheStorage = JSON.parse(updatedCacheStorageData);

            if (updatedCacheStorage.cart) {
                const updatedCartId = updatedCacheStorage.cart.cartId;
                const updatedStoreId = updatedCacheStorage.cart.storeId;

                if ((updatedCartId !== '') && (updatedStoreId !== '')) {
                    cartId = updatedCartId;
                    storeId = updatedStoreId;
                    callFetchConfigData();

                    return true;
                }
            }
        }

        return false;
    }

    function checkCartUpdated() {
        const currentPrice = getPriceWithoutCurrency(configData.priceWrapper);

        if (currentPrice && currentPrice !== lastKnownPrice) {
            lastKnownPrice = currentPrice;

            updateWidgetInstance();
        }
    }

    function callFetchConfigData() {
        if (cartId && storeId) {
            fetchConfigData()
                .then(theConfig => {
                    configData = theConfig;
                    if (configData && configData.priceWrapper) {
                        processAfterpay();
                    }
                })
                .catch(error => console.error("Error: ", error));
        }
    }

    function updateWidgetInstance() {
        const squarePlacementSelector = document.getElementById(configData.squarePlacementId);

        if (squarePlacementSelector) {
            squarePlacementSelector.outerHTML = "";
        }

        const cacheStorageData = localStorage.getItem('mage-cache-storage');
        const cacheStorage = JSON.parse(cacheStorageData);

        if (!cacheStorage.cart && !cacheStorage.cart.subtotalAmount) {
            return;
        }

        const subtotalAmount = cacheStorage.cart.subtotalAmount;
        let priceAmount = parseFloat(subtotalAmount);

        updateAfterpayAmount(priceAmount);
    }

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

    function updateAfterpayAmount(amount) {
        let wrapperHtml = document.querySelector(configData.widgetContainer),
            dataCurrency = configData?.dataCurrency ? configData.dataCurrency : window.afterpayCurrency;

        const blockHtml = '<square-placement id="' + configData.squarePlacementId + '"' +
            'data-show-lower-limit="' + configData.dataShowLowerLimit + '"' +
            'data-currency="' + dataCurrency + '"' +
            'data-locale="' + configData.dataLocale + '"' +
            'data-is-eligible="' + configData.dataIsEligible + '"' +
            'data-amount="' + amount + '"' +
            'data-mpid="' + configData.dataMPID + '"' +
            'data-cbt-enabled="' + configData.dataCbtEnabledString + '"' +
            'data-platform="' + configData.dataPlatform + '"' +
            'data-page-type="' + configData.dataPageType + '"></square-placement>';

        if (wrapperHtml) {
            wrapperHtml.insertAdjacentHTML('afterend', blockHtml);
        }
    }

    window.addEventListener('load', function() {
        observeCacheStorageChanges();
    });
})(document, window, 'script');
