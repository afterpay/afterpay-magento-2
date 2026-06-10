/**
 * Afterpay - Hyva Checkout Title Replacer
 * Replaces the Afterpay payment method title with Square Placement logo
 */
(function () {
    'use strict';

    /**
     * Main Afterpay component
     */
    window.afterpayComponent = function () {
        return {
            isVisible: true,
            currentMethod: '',
            currency: '',
            trigger: 'afterpay-hyva-checkout',
            mpid: '',
            countryCode: '',
            orderTotal: '0.00',
            shippingAddress: '',
            termsConditionsUrl: '',
            platform: 'Magento',
            pageType: 'checkout',
            placeOrderBtn: '.btn-place-order',
            afterpayLink: 'https://www.afterpay.com',
            redirectCheckoutUrl: '',
            dataReady: false,

            /**
             * Initialize the component (called by Alpine.js)
             */
            init() {
                this.fetchMpid();
                this.fetchOrderTotal();
                this.initAfterpay();
                this.setTermsConditionsUrl();
            },

            /**
             * Check if all required data is loaded and dispatch event
             */
            checkDataReady() {
                if (this.mpid && this.currency && this.orderTotal && !this.dataReady) {
                    this.dataReady = true;

                    // Inject the Square placement directly
                    this.injectSquarePlacement();

                    // Also dispatch event for compatibility
                    window.dispatchEvent(new CustomEvent('afterpay:data-ready', {
                        detail: {
                            mpid: this.mpid,
                            currency: this.currency,
                            orderTotal: this.orderTotal
                        }
                    }));
                }
            },

            /**
             * Inject Square placement logo
             */
            injectSquarePlacement() {
                const placementWrapper = document.querySelector('.afterpay-logo-placement');
                if (!placementWrapper) {
                    return;
                }

                // Check if already injected
                if (placementWrapper.querySelector('square-placement')) {
                    return;
                }

                // Remove loading text
                const loadingDiv = document.getElementById('afterpay-logo-loading');
                if (loadingDiv) {
                    loadingDiv.remove();
                }

                // Create square placement element
                const squarePlacement = document.createElement('square-placement');
                squarePlacement.setAttribute('data-mpid', this.mpid);
                squarePlacement.setAttribute('data-amount', this.orderTotal);
                squarePlacement.setAttribute('data-currency', this.currency);
                squarePlacement.setAttribute('data-platform', 'Magento');
                squarePlacement.setAttribute('data-type', 'logo');
                squarePlacement.setAttribute('data-page-type', 'checkout');

                placementWrapper.appendChild(squarePlacement);
            },

            /**
             * Handles payment method selection. Stores the selected method and toggles the place order button visibility.
             */
            onPaymentMethodSelect(methodCode) {
                this.currentMethod = methodCode;
                const orderBtn = document.querySelector(this.placeOrderBtn);

                if (orderBtn) {
                    if (methodCode === 'afterpay') {
                        orderBtn.classList.add('hidden');
                    } else {
                        orderBtn.classList.remove('hidden');
                    }
                }
            },

            /**
             * Checks and handles the currently selected payment method on component init.
             */
            checkInitialPaymentMethod() {
                setTimeout(() => {
                    const checked = document.querySelector('input[name=\"payment-method-option\"]:checked');
                    if (checked && checked.value) {
                        this.onPaymentMethodSelect(checked.value);
                    }
                }, 0);
            },

            /**
             * Returns the parsed mage-cache-storage object from localStorage.
             */
            getMageCacheStorage() {
                try {
                    return JSON.parse(localStorage.getItem('mage-cache-storage'));
                } catch (error) {
                    return {};
                }
            },

            /**
             * Sets the Terms & Conditions URL based on the currency.
             */
            setTermsConditionsUrl() {
                this.termsConditionsUrl = this.getTermsLink();
            },

            /**
             * Fetches Afterpay MPID via GraphQL query.
             */
            async fetchMpid() {
                const query = `
                        query {
                            afterpayConfig {
                                mpid
                            }
                        }
                    `;

                try {
                    const response = await this.executeGraphqlQuery(query);
                    if (response?.data?.afterpayConfig?.mpid) {
                        this.mpid = response.data.afterpayConfig.mpid;
                        this.checkDataReady();
                    }
                } catch (error) {
                    // Silently fail
                }
            },

            /**
             * Retrieves the cart ID from mage-cache-storage in localStorage.
             */
            getCartId() {
                try {
                    const storageData = this.getMageCacheStorage();
                    const cartId = storageData?.cart?.cartId;

                    if (!cartId) {
                        throw new Error('Cart ID not found in mage-cache-storage.');
                    }
                    return cartId;
                } catch (error) {
                    return null;
                }
            },

            /**
             * Fetches the order total and currency via GraphQL query.
             */
            fetchOrderTotal() {
                const cartId = this.getCartId();
                if (!cartId) {
                    return;
                }

                const query = `
                        query getCart($cartId: String!) {
                            cart(cart_id: $cartId) {
                                prices {
                                    grand_total {
                                        value
                                        currency
                                    }
                                }
                            }
                        }
                    `;

                this.executeGraphqlQuery(query, {cartId})
                    .then(response => {
                        if (response?.data?.cart?.prices?.grand_total) {
                            const grandTotal = response.data.cart.prices.grand_total;
                            this.orderTotal = grandTotal.value;
                            this.currency = grandTotal.currency;
                            this.checkDataReady();
                        }
                    })
                    .catch(error => {
                    });
            },

            /**
             * Initializes the AfterPay widget with the required parameters.
             */
            initAfterpay() {
                if (!this.mpid) {
                    return;
                }

                Square.Marketplace.initializeForRedirect({
                    countryCode: this.getCountryCode().toUpperCase(),
                    buyNow: true,
                    pickup: false,
                    target: "#" + this.trigger,
                    onCommenceCheckout: actions => this.getAfterpayToken(actions),
                });
            },

            /**
             * Fetches the Afterpay token for payment (via API).
             */
            getAfterpayToken(actions) {
                const cartId = this.getCartId(),
                    confirmPath = 'afterpay/payment/capture',
                    cancelPath = 'afterpay/payment/capture';

                const mutation = `
                        mutation CreateAfterpayCheckout($cartId: String!, $cancelPath: String!, $confirmPath: String!) {
                            createAfterpayCheckout(input: {
                                cart_id: $cartId
                                redirect_path: {
                                    cancel_path: $cancelPath
                                    confirm_path: $confirmPath
                                }
                            }) {
                                afterpay_token
                                afterpay_redirectCheckoutUrl
                            }
                        }
                    `;

                this.executeGraphqlQuery(mutation, {cartId, cancelPath, confirmPath})
                    .then(response => {
                        if (response?.data.createAfterpayCheckout.afterpay_token && response?.data.createAfterpayCheckout.afterpay_redirectCheckoutUrl) {
                            window.location.href = response.data.createAfterpayCheckout.afterpay_redirectCheckoutUrl;
                        } else {
                            Square.Marketplace.close();
                            actions.reject(Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                        }
                    })
                    .catch(error => {
                    });
            },

            /**
             * Executes a GraphQL query to the Magento server.
             */
            async executeGraphqlQuery(query, variables = {}) {
                const graphqlEndpoint = `${window.location.origin}/graphql`;
                const body = JSON.stringify({query, variables});
                const storageData = this.getMageCacheStorage();
                const storeViewCode = storageData?.cart?.storeViewCode;
                const customerToken = storageData?.customer?.signin_token;

                const headers = {'Content-Type': 'application/json', 'Store': storeViewCode};
                if (customerToken) {
                    headers['Authorization'] = `Bearer ${customerToken}`;
                }

                const response = await fetch(graphqlEndpoint, {
                    method: 'POST',
                    headers,
                    body,
                });

                if (!response.ok) {
                    throw new Error(`GraphQL query failed: ${response.statusText}`);
                }
                return response.json();
            },

            /**
             * Converts an object to a URL-encoded string (application/x-www-form-urlencoded).
             */
            objectToUrlEncoded(obj) {
                return Object.keys(obj)
                    .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]))
                    .join('&');
            },

            /**
             * Returns the terms text depending on the currency.
             */
            getTermsText() {
                switch (this.currency) {
                    case 'USD':
                        return window.afterpayTermsTranslations.USD;
                    case 'CAD':
                        return window.afterpayTermsTranslations.CAD;
                    default:
                        return window.afterpayTermsTranslations.DEFAULT;
                }
            },

            /**
             * Returns the Afterpay terms link depending on the currency.
             */
            getTermsLink() {
                switch (this.currency) {
                    case 'USD':
                        return this.afterpayLink + '/en-US/installment-agreement';
                    case 'CAD':
                        return this.afterpayLink + '/en-CA/instalment-agreement';
                    case 'NZD':
                        return this.afterpayLink + '/en-NZ/terms-of-service';
                    case 'AUD':
                        return this.afterpayLink + '/en-AU/terms-of-service';
                    default:
                        return this.afterpayLink + '/terms/';
                }
            },

            /**
             * Returns the Afterpay terms link depending on the currency.
             */
            getCountryCode() {
                switch (this.currency) {
                    case 'USD':
                        return 'US';
                    case 'CAD':
                        return 'CA';
                    case 'NZD':
                        return 'NZ';
                    case 'AUD':
                        return 'AU';
                    default:
                        return this.countryCode;
                }
            },
        };
    };

    // Store component instance globally so we can access it
    let afterpayComponentInstance = null;

    /**
     * Replace the Afterpay payment method title with Square logo placement
     */
    function replaceAfterpayTitle() {
        // Find the Afterpay payment method option
        const afterpayLabel = document.querySelector('label[for="payment-method-afterpay"]');

        if (!afterpayLabel) {
            return;
        }

        // Find the title div within the label
        const titleDiv = afterpayLabel.querySelector('.text-gray-700.font-bold');

        if (!titleDiv) {
            return;
        }

        // Check if we already replaced the title
        if (titleDiv.querySelector('.afterpay-logo-placement')) {
            return;
        }

        // Create a wrapper with Alpine component
        const placementWrapper = document.createElement('div');
        placementWrapper.className = 'afterpay-logo-placement';
        placementWrapper.setAttribute('x-data', 'afterpayComponentInstance = afterpayComponent()');

        // Initially show loading text
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'text-sm text-gray-500';
        loadingDiv.textContent = 'Loading...';
        loadingDiv.id = 'afterpay-logo-loading';
        placementWrapper.appendChild(loadingDiv);

        // Replace the original title text with our wrapper
        titleDiv.textContent = '';
        titleDiv.appendChild(placementWrapper);

        // Initialize Alpine.js on the wrapper
        if (window.Alpine) {
            window.Alpine.initTree(placementWrapper);
        }
    }

    /**
     * Inject Square placement logo once data is ready
     */
    function injectSquarePlacement(data) {
        const placementWrapper = document.querySelector('.afterpay-logo-placement');
        if (!placementWrapper) {
            return;
        }

        // Check if already injected
        if (placementWrapper.querySelector('square-placement')) {
            return;
        }

        // Remove loading text
        const loadingDiv = document.getElementById('afterpay-logo-loading');
        if (loadingDiv) {
            loadingDiv.remove();
        }

        // Validate data
        if (!data.mpid || !data.currency || !data.orderTotal) {
            return;
        }

        // Create square placement element
        const squarePlacement = document.createElement('square-placement');
        squarePlacement.setAttribute('data-mpid', data.mpid);
        squarePlacement.setAttribute('data-amount', data.orderTotal);
        squarePlacement.setAttribute('data-currency', data.currency);
        squarePlacement.setAttribute('data-platform', 'Magento');
        squarePlacement.setAttribute('data-type', 'logo');
        squarePlacement.setAttribute('data-page-type', 'checkout');

        placementWrapper.appendChild(squarePlacement);
    }

    /**
     * Initialize the title replacement
     */
    function init() {
        // Listen for data ready event
        window.addEventListener('afterpay:data-ready', (event) => {
            if (event.detail) {
                injectSquarePlacement(event.detail);
            }
        });

        // Polling fallback: check if component data is ready
        let checkCount = 0;
        const checkDataInterval = setInterval(() => {
            checkCount++;

            if (afterpayComponentInstance && afterpayComponentInstance.dataReady) {
                clearInterval(checkDataInterval);
                injectSquarePlacement({
                    mpid: afterpayComponentInstance.mpid,
                    currency: afterpayComponentInstance.currency,
                    orderTotal: afterpayComponentInstance.orderTotal
                });
            } else if (checkCount > 50) {
                // Stop checking after 10 seconds (50 * 200ms)
                clearInterval(checkDataInterval);
            }
        }, 200);

        // Try to replace immediately
        replaceAfterpayTitle();

        // Also listen for checkout step loaded event
        window.addEventListener('checkout:step:loaded', function () {
            replaceAfterpayTitle();
        });

        // Listen for payment method activation
        window.addEventListener('checkout:payment:method-activate', (event) => {
            if (event.detail && event.detail.method) {
                setTimeout(replaceAfterpayTitle, 100);
            }
        });

        // Listen for payment method list updates
        window.addEventListener('checkout:payment:method-list-updated', function () {
            setTimeout(replaceAfterpayTitle, 100);
        });

        // Use MutationObserver to catch dynamic changes
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.addedNodes.length) {
                    replaceAfterpayTitle();
                }
            });
        });

        // Observe the payment methods container
        const paymentMethodsList = document.getElementById('payment-method-list');
        if (paymentMethodsList) {
            observer.observe(paymentMethodsList, {
                childList: true,
                subtree: true
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

