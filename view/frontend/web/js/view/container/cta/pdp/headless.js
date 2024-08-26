(function (d, w, s) {
    let query = `mutation {
                    getAfterpayConfigPdp(input: {
                        product_sku: "${afterpayProductSku}"
                        store_id: "${afterpayStoreId}"
                    }) {
                        allowed_currencies
                        is_enabled
                        mpid
                        is_enabled_cta_pdp_headless
                        product_type
                        show_lover_limit
                        is_product_allowed
                        is_cbt_enabled
                        placement_after_selector
                        placement_after_selector_bundle
                        price_selector
                        price_selector_bundle
                    }
                }`;

    let graphqlEndpoint = window.location.origin + '/graphql';

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
                    const afterpayConfig = data.data.getAfterpayConfigPdp;

                    if (afterpayConfig.is_enabled && afterpayConfig.is_enabled_cta_pdp_headless) {
                        let dataMPID = afterpayConfig.mpid,
                            dataShowLowerLimit = afterpayConfig.show_lover_limit,
                            dataPlatform = 'Magento',
                            dataPageType = 'product',
                            dataIsEligible = afterpayConfig.is_product_allowed,
                            dataCbtEnabledString = Boolean(afterpayConfig.is_cbt_enabled).toString(),
                            dataProductType = afterpayConfig.product_type,
                            squarePlacementId = 'afterpay-cta-pdp',
                            widgetContainer = afterpayConfig.placement_after_selector,
                            widgetContainerBundle = afterpayConfig.placement_after_selector_bundle,
                            priceWrapper = afterpayConfig.price_selector,
                            priceWrapperBundle = afterpayConfig.price_selector_bundle;

                        return {
                            dataShowLowerLimit: dataShowLowerLimit,
                            dataCurrency: afterpayCurrency,
                            dataLocale: afterpayLocale,
                            dataIsEligible: dataIsEligible,
                            dataMPID: dataMPID,
                            dataCbtEnabledString: dataCbtEnabledString,
                            dataPlatform: dataPlatform,
                            dataPageType: dataPageType,
                            dataProductType: dataProductType,
                            widgetContainer: widgetContainer,
                            widgetContainerBundle: widgetContainerBundle,
                            squarePlacementId: squarePlacementId,
                            priceWrapper: priceWrapper,
                            priceWrapperBundle: priceWrapperBundle
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

    // Process the config data
    function processAfterpay() {
        fetchConfigData()
            .then(configData => {
                if (configData && !(configData.dataProductType === 'grouped')) {
                    updateAfterpayAmount(configData);
                }
            })
            .catch(error => console.error("Error: ", error));
    }

    //  Add the widget
    function updateAfterpayAmount(configData) {
        let wrapperHtml = document.querySelector(configData.widgetContainer),
            priceWrapper = configData.priceWrapper;

        if (configData.dataProductType === 'bundle') {
            wrapperHtml = document.querySelector(configData.widgetContainerBundle);
            priceWrapper = configData.priceWrapperBundle;
        }

        const blockHtml = '<square-placement id="' + configData.squarePlacementId + '"' +
            'data-show-lower-limit="' + configData.dataShowLowerLimit + '"' +
            'data-currency="' + configData.dataCurrency + '"' +
            'data-locale="' + configData.dataLocale + '"' +
            'data-is-eligible="' + configData.dataIsEligible + '"' +
            'data-amount-selector="' + priceWrapper + '"' +
            'data-mpid="'+ configData.dataMPID + '"' +
            'data-cbt-enabled="'+ configData.dataCbtEnabledString + '"' +
            'data-platform="'+ configData.dataPlatform + '"' +
            'data-page-type="' + configData.dataPageType + '"></square-placement>';

        if (wrapperHtml) {
            wrapperHtml.insertAdjacentHTML('afterend', blockHtml);
        }
    }

    processAfterpay();
})(document, window, 'script');
