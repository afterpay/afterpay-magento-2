(function (d, w, s) {
    let query = `mutation {
                    getAfterpayConfigPdp(input: {
                        product_sku: "${afterpayProductSku}"
                        store_id: "${afterpayStoreId}"
                    }) {
                        allowed_currencies
                        is_enabled
                        mpid
                        is_enabled_ec_pdp_headless
                        product_type
                        show_lover_limit
                        is_product_allowed
                        placement_after_selector
                        placement_after_selector_bundle
                        is_cbt_enabled
                        max_amount
                        min_amount
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
                    if(data.errors) {
                        console.error("Error:", data.errors[0].message);
                        return null;
                    }
                    const afterpayConfig = data.data.getAfterpayConfigPdp;

                    if(afterpayConfig) {
                        const event = new CustomEvent('showHeadlessEC', { detail: { afterpayConfig} });
                        document.dispatchEvent(event);
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
    
    window.addEventListener("load", (event) => {
        if (afterpayProductSku !== "") {
            fetchConfigData();
        }
    });

})(document, window, 'script');
