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
                 is_enabled_ec_minicart_headless
                 show_lover_limit
                 is_product_allowed
                 is_cbt_enabled
                 placement_wrapper
                 placement_after_selector
                 price_selector
                 max_amount
                 min_amount
                 is_virtual
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

                    if (afterpayConfig) {
                        const event = new CustomEvent('showHeadlessMinicart', {detail: {afterpayConfig}});
                        document.dispatchEvent(event);
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
                        fetchConfigData();
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
                    fetchConfigData();

                    return true;
                }
            }
        }

        return false;
    }

    window.addEventListener('load', function () {
        observeCacheStorageChanges();
    });

    // Create the custom event
    window.reloadMinicart = new CustomEvent('reloadMinicart');

    // Attach an event listener
    document.addEventListener('reloadMinicart', function() {
        if(cartId && storeId) {
            fetchConfigData();
        }
    });

})(document, window, 'script');
