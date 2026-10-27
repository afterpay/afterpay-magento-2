/**
 * Afterpay - Hyva Checkout Integration
 *
 * Fetches Afterpay config via GraphQL, then imperatively builds the UI:
 *  - Replaces the payment method title label with a Square logo placement.
 *  - Injects the payment-schedule widget and terms copy into afterpay.phtml's
 *    plain-HTML placeholders (no Alpine directives — avoids CSP unsafe-eval).
 *  - Hides the default "Place Order" button while Afterpay is selected.
 *  - Initialises Square.Marketplace for the redirect checkout flow.
 *
 * Global state used for cross-instance persistence (Magewire re-inits):
 *   window._afterpayCache    – fetched data, survives component re-creation.
 *   window._afterpayFetching – prevents duplicate in-flight GraphQL requests.
 *   window._afterpayDataHandler – single live listener for afterpay:data-ready.
 *
 * Order total is re-fetched when shipping changes or the payment step loads so
 * Square placement widgets stay in sync with the updated grand total.
 */
(function () {
    'use strict';

    // ─── Component factory ────────────────────────────────────────────────────
    // Used only for data-fetching and the Square redirect checkout flow.
    // NOT registered with Alpine.data — afterpay.phtml has no Alpine directives.

    window.afterpayComponent = function () {
        return {
            mpid: '',
            currency: '',
            orderTotal: '0.00',
            countryCode: '',
            termsConditionsUrl: '',
            dataReady: false,
            afterpayLink: 'https://www.afterpay.com',
            trigger: 'afterpay-hyva-checkout',

            init() {
                const applyData = (data) => {
                    this.mpid               = data.mpid;
                    this.currency           = data.currency;
                    this.orderTotal         = data.orderTotal;
                    this.countryCode        = data.countryCode || '';
                    this.termsConditionsUrl = data.termsConditionsUrl || '';
                    this.dataReady          = true;
                    this.initAfterpay();
                };

                // Data already loaded by a previous instance — restore immediately.
                // Also re-dispatch the event so the bootstrap listeners rebuild
                // the title logo and payment content (e.g. after switching methods).
                if (window._afterpayCache?.mpid) {
                    applyData(window._afterpayCache);
                    window.dispatchEvent(new CustomEvent('afterpay:data-ready', {detail: window._afterpayCache}));
                    return;
                }

                // Replace any stale listener from a dead previous instance so we
                // never accumulate handlers and always notify the CURRENT instance.
                if (window._afterpayDataHandler) {
                    window.removeEventListener('afterpay:data-ready', window._afterpayDataHandler);
                }
                window._afterpayDataHandler = (e) => applyData(e.detail);
                window.addEventListener('afterpay:data-ready', window._afterpayDataHandler, {once: true});

                // Only one global fetch in flight at a time.
                if (!window._afterpayFetching) {
                    window._afterpayFetching = true;
                    this.fetchMpid();
                    this.fetchOrderTotal();
                    this.setTermsConditionsUrl();
                }
            },

            checkDataReady() {
                if (this.mpid && this.currency && this.orderTotal && !this.dataReady) {
                    this.dataReady = true;
                    const data = {
                        mpid:               this.mpid,
                        currency:           this.currency,
                        orderTotal:         this.orderTotal,
                        countryCode:        this.countryCode,
                        termsConditionsUrl: this.termsConditionsUrl,
                    };
                    window._afterpayCache    = data;
                    window._afterpayFetching = false;
                    window.dispatchEvent(new CustomEvent('afterpay:data-ready', {detail: data}));
                }
            },

            getMageCacheStorage() {
                try {
                    return JSON.parse(localStorage.getItem('mage-cache-storage'));
                } catch (e) {
                    return {};
                }
            },

            setTermsConditionsUrl() {
                this.termsConditionsUrl = this.getTermsLink();
            },

            async fetchMpid() {
                const query = `query { afterpayConfig { mpid } }`;
                try {
                    const response = await this.executeGraphqlQuery(query);
                    if (response?.data?.afterpayConfig?.mpid) {
                        this.mpid = response.data.afterpayConfig.mpid;
                        this.checkDataReady();
                    }
                } catch (e) {
                    // Silently fail
                }
            },

            getCartId() {
                try {
                    const storageData = this.getMageCacheStorage();
                    // Hyva stores the masked guest cart id as cartId or guestMaskId
                    // depending on the version; try both.
                    return storageData?.cart?.cartId
                        || storageData?.cart?.guestMaskId
                        || null;
                } catch (e) {
                    return null;
                }
            },

            async fetchOrderTotal() {
                try {
                    const cartId = this.getCartId();

                    if (cartId) {
                        const query = `
                            query getCart($cartId: String!) {
                                cart(cart_id: $cartId) {
                                    prices { grand_total { value currency } }
                                }
                            }
                        `;
                        const response = await this.executeGraphqlQuery(query, {cartId});
                        if (response?.data?.cart?.prices?.grand_total) {
                            const gt = response.data.cart.prices.grand_total;
                            this.orderTotal = gt.value;
                            this.currency = gt.currency;
                            this.checkDataReady();
                            return;
                        }
                    }

                    // Fallback: customerCart works for logged-in users without
                    // needing a cart_id (guests without cartId in localStorage
                    // will get a GraphQL error and we silently skip it).
                    const fallback = await this.executeGraphqlQuery(
                        `{ customerCart { prices { grand_total { value currency } } } }`
                    );
                    if (fallback?.data?.customerCart?.prices?.grand_total) {
                        const gt = fallback.data.customerCart.prices.grand_total;
                        this.orderTotal = gt.value;
                        this.currency = gt.currency;
                        this.checkDataReady();
                    }
                } catch (e) {}
            },

            initAfterpay() {
                if (!this.mpid) return;
                Square.Marketplace.initializeForRedirect({
                    countryCode: this.getCountryCode().toUpperCase(),
                    buyNow: true,
                    pickup: false,
                    target: '#' + this.trigger,
                    onCommenceCheckout: actions => this.getAfterpayToken(actions),
                });
            },

            async getAfterpayToken(actions) {
                const cartId = this.getCartId(),
                    confirmPath = 'afterpay/payment/capture',
                    cancelPath  = 'afterpay/payment/capture';

                try {
                    if (!cartId || typeof hyvaCheckout === 'undefined') {
                        throw new Error('Unable to initialize the Afterpay checkout.');
                    }

                    // The Square button bypasses Hyvä's regular place-order button.
                    // Flush pending Magewire form changes and run the same client-side
                    // validation preflight before creating the redirect checkout.
                    await hyvaCheckout.navigation.executeTasks();
                    const isValid = await hyvaCheckout.validation.validate();
                    await hyvaCheckout.isIdle();

                    if (isValid === false) {
                        Square.Marketplace.close();
                        actions.reject(Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                        return;
                    }

                    const mutation = `
                        mutation CreateAfterpayCheckout($cartId: String!, $cancelPath: String!, $confirmPath: String!) {
                            createAfterpayCheckout(input: {
                                cart_id: $cartId
                                redirect_path: { cancel_path: $cancelPath confirm_path: $confirmPath }
                            }) {
                                afterpay_token
                                afterpay_redirectCheckoutUrl
                            }
                        }
                    `;
                    const response = await this.executeGraphqlQuery(
                        mutation,
                        {cartId, cancelPath, confirmPath}
                    );
                    const checkout = response?.data?.createAfterpayCheckout;

                    if (checkout?.afterpay_token && checkout?.afterpay_redirectCheckoutUrl) {
                        window.location.href = checkout.afterpay_redirectCheckoutUrl;
                        return;
                    }

                    throw new Error('Afterpay did not return a checkout token.');
                } catch (error) {
                    Square.Marketplace.close();
                    actions.reject(Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                }
            },

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

                const response = await fetch(graphqlEndpoint, {method: 'POST', headers, body});
                if (!response.ok) throw new Error(`GraphQL query failed: ${response.statusText}`);
                return response.json();
            },

            getTermsText() {
                const template = document.getElementById('afterpay-hyva-template');
                const ds = template?.dataset || {};
                return {USD: ds.termsUsd, CAD: ds.termsCad}[this.currency] || ds.termsDefault || '';
            },

            getTermsLink() {
                switch (this.currency) {
                    case 'USD': return this.afterpayLink + '/en-US/installment-agreement';
                    case 'CAD': return this.afterpayLink + '/en-CA/instalment-agreement';
                    case 'NZD': return this.afterpayLink + '/en-NZ/terms-of-service';
                    case 'AUD': return this.afterpayLink + '/en-AU/terms-of-service';
                    default:    return this.afterpayLink + '/terms/';
                }
            },

            getCountryCode() {
                switch (this.currency) {
                    case 'USD': return 'US';
                    case 'CAD': return 'CA';
                    case 'NZD': return 'NZ';
                    case 'AUD': return 'AU';
                    default:    return this.countryCode;
                }
            },
        };
    };

    // ─── Payment method selection ─────────────────────────────────────────────
    // Hides the default "Place Order" button while Afterpay is the active method.
    // currentPaymentMethod persists across Magewire re-renders so we can re-apply
    // after the button is replaced in the DOM.

    let currentPaymentMethod = '';

    function applyOrderButtonVisibility() {
        const orderBtn = document.querySelector('.btn-place-order');
        if (orderBtn) {
            orderBtn.classList.toggle('hidden', currentPaymentMethod === 'afterpay');
        }
    }

    function onPaymentMethodSelect(methodCode) {
        currentPaymentMethod = methodCode;
        applyOrderButtonVisibility();
    }

    // ─── Payment content injection ────────────────────────────────────────────
    // Builds the payment-schedule widget and terms text into afterpay.phtml's
    // plain-HTML placeholder elements. Re-creates square-placement on each call
    // so amount updates after shipping/total changes are reflected.

    function upsertSquarePlacement(container, data, type) {
        if (!container || !data?.mpid || !data?.currency || data.orderTotal == null || data.orderTotal === '') {
            return;
        }

        container.querySelectorAll('square-placement').forEach((el) => el.remove());

        const sp = document.createElement('square-placement');
        sp.setAttribute('data-mpid', data.mpid);
        sp.setAttribute('data-amount', data.orderTotal);
        sp.setAttribute('data-currency', data.currency);
        sp.setAttribute('data-type', type);
        sp.setAttribute('data-platform', 'Magento');
        sp.setAttribute('data-page-type', 'checkout');
        keepLatestWidgetOnly(sp);
        container.appendChild(sp);
    }

    // square-placement mounts a new zoid widget on every connectedCallback
    // without removing the previous one, so when Magewire morphs detach and
    // re-attach the element the widgets stack up. Keep only the newest.
    function keepLatestWidgetOnly(placement) {
        new MutationObserver(() => {
            const widgets = placement.querySelectorAll(':scope > [id^="zoid-"]');
            for (let i = 0; i < widgets.length - 1; i++) {
                widgets[i].remove();
            }
        }).observe(placement, {childList: true});
    }

    function injectPaymentContent(data) {
        if (!data.mpid || !data.currency || data.orderTotal == null || data.orderTotal === '') return;

        upsertSquarePlacement(
            document.getElementById('afterpay-payment-schedule-container'),
            data,
            'payment-schedule'
        );

        const termsTextEl = document.getElementById('afterpay-terms-text');
        if (termsTextEl && !termsTextEl.textContent.trim()) {
            const template = document.getElementById('afterpay-hyva-template');
            const ds = template?.dataset || {};
            const termsMap = {
                USD: ds.termsUsd,
                CAD: ds.termsCad,
            };
            termsTextEl.textContent = termsMap[data.currency] || ds.termsDefault || '';
        }

        const termsLinkEl = document.getElementById('afterpay-terms-link');
        if (termsLinkEl && data.termsConditionsUrl) {
            termsLinkEl.href = data.termsConditionsUrl;
        }

        const termsContainerEl = document.getElementById('afterpay-terms-container');
        if (termsContainerEl) termsContainerEl.style.display = '';

        const btnEl = document.getElementById('afterpay-hyva-checkout');
        if (btnEl) {
            const countryCode = data.countryCode || (data.currency === 'USD' ? 'US' : '');
            btnEl.classList.toggle('afterpay-green', countryCode === 'US');
        }
    }

    // ─── Title replacer ───────────────────────────────────────────────────────

    let afterpayComponentInstance = null;

    function replaceAfterpayTitle() {
        const afterpayLabel = document.querySelector('label[for="payment-method-afterpay"]');
        let afterpaySpan = null;

        if (!afterpayLabel) {
            afterpaySpan = document.querySelector('#payment-method-option-afterpay > label > span');
            if (!afterpaySpan) return;
        }

        let titleDiv = null;
        if (!afterpayLabel && afterpaySpan) {
            titleDiv = afterpaySpan;
        } else {
            titleDiv = afterpayLabel.querySelector('.text-gray-700.font-bold');
        }

        if (!titleDiv) return;
        if (titleDiv.querySelector('.afterpay-logo-placement')) return;

        const placementWrapper = document.createElement('div');
        placementWrapper.className = 'afterpay-logo-placement';

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'text-sm text-gray-500';
        loadingDiv.textContent = 'Loading...';
        loadingDiv.id = 'afterpay-logo-loading';
        placementWrapper.appendChild(loadingDiv);

        titleDiv.textContent = '';
        titleDiv.appendChild(placementWrapper);

        afterpayComponentInstance = window.afterpayComponent();
        afterpayComponentInstance.init();
    }

    function injectSquarePlacement(data) {
        const placementWrapper = document.querySelector('.afterpay-logo-placement');
        if (!placementWrapper) return;

        const loadingDiv = document.getElementById('afterpay-logo-loading');
        if (loadingDiv) loadingDiv.remove();

        upsertSquarePlacement(placementWrapper, data, 'logo');
    }

    // ─── Order total refresh ──────────────────────────────────────────────────
    // Re-fetches grand total after shipping changes and re-renders widgets.

    let refreshOrderTotalTimeout = null;
    let refreshOrderTotalInFlight = null;

    function scheduleOrderTotalRefresh(delayMs = 350) {
        clearTimeout(refreshOrderTotalTimeout);
        refreshOrderTotalTimeout = setTimeout(() => {
            if (!refreshOrderTotalInFlight) {
                refreshOrderTotalInFlight = refreshCachedOrderTotal().finally(() => {
                    refreshOrderTotalInFlight = null;
                });
            }
        }, delayMs);
    }

    async function refreshCachedOrderTotal() {
        if (!window._afterpayCache?.mpid) return;

        const component = afterpayComponentInstance || window.afterpayComponent();
        component.mpid = window._afterpayCache.mpid;
        component.currency = window._afterpayCache.currency;
        component.countryCode = window._afterpayCache.countryCode || '';
        component.termsConditionsUrl = window._afterpayCache.termsConditionsUrl || component.getTermsLink();

        await component.fetchOrderTotal();

        if (!component.orderTotal || !component.currency) return;

        window._afterpayCache = Object.assign({}, window._afterpayCache, {
            orderTotal: component.orderTotal,
            currency: component.currency,
        });

        if (afterpayComponentInstance) {
            afterpayComponentInstance.orderTotal = component.orderTotal;
            afterpayComponentInstance.currency = component.currency;
        }

        window.dispatchEvent(new CustomEvent('afterpay:data-ready', {detail: window._afterpayCache}));
    }

    // ─── Bootstrap ────────────────────────────────────────────────────────────

    function init() {
        // When Afterpay data arrives (first fetch or cached re-init), build both
        // the title logo and the payment-schedule / terms content.
        window.addEventListener('afterpay:data-ready', (event) => {
            if (!event.detail) return;
            injectSquarePlacement(event.detail);
            injectPaymentContent(event.detail);
        });

        // Polling fallback: catches the case where the title replacer creates its
        // own instance that fetches data without going through the event bus.
        let checkCount = 0;
        const checkDataInterval = setInterval(() => {
            checkCount++;
            if (afterpayComponentInstance && afterpayComponentInstance.dataReady) {
                clearInterval(checkDataInterval);
                const data = {
                    mpid:               afterpayComponentInstance.mpid,
                    currency:           afterpayComponentInstance.currency,
                    orderTotal:         afterpayComponentInstance.orderTotal,
                    countryCode:        afterpayComponentInstance.countryCode,
                    termsConditionsUrl: afterpayComponentInstance.termsConditionsUrl,
                };
                injectSquarePlacement(data);
                injectPaymentContent(data);
            } else if (checkCount > 50) {
                clearInterval(checkDataInterval);
            }
        }, 200);

        replaceAfterpayTitle();

        // Detect the initially-selected payment method. Afterpay being pre-selected
        // is most reliably detected by its template div being in the DOM. We retry
        // several times because Magewire may render the Place Order button after
        // DOMContentLoaded, and subsequent Magewire updates may un-hide it.
        function detectInitialMethod() {
            if (document.getElementById('afterpay-hyva-template')) {
                onPaymentMethodSelect('afterpay');
            } else {
                const checked = document.querySelector('input[name="payment-method-option"]:checked');
                if (checked?.value) onPaymentMethodSelect(checked.value);
            }
        }
        [0, 300, 700, 1500].forEach(ms => setTimeout(detectInitialMethod, ms));

        // Re-apply button visibility after every Magewire server round-trip —
        // Magewire morphs the DOM and can restore the button without our class.
        const reapplyAfterMagewire = () => applyOrderButtonVisibility();
        window.addEventListener('livewire:message.processed', reapplyAfterMagewire);
        window.addEventListener('message.processed', reapplyAfterMagewire);

        window.addEventListener('checkout:step:loaded', (event) => {
            if (event.detail?.route === 'payment') {
                scheduleOrderTotalRefresh();
            }
            replaceAfterpayTitle();
            detectInitialMethod();
        });

        window.addEventListener('checkout:shipping:method-activate', scheduleOrderTotalRefresh);
        window.addEventListener('shipping:method:update', scheduleOrderTotalRefresh);

        window.addEventListener('checkout:payment:method-activate', (event) => {
            if (!event.detail?.method) return;
            onPaymentMethodSelect(event.detail.method);
            setTimeout(replaceAfterpayTitle, 100);
            if (event.detail.method === 'afterpay') {
                scheduleOrderTotalRefresh();
            }
        });

        window.addEventListener('checkout:payment:method-list-updated', () => {
            setTimeout(replaceAfterpayTitle, 100);
        });

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (!mutation.addedNodes.length) continue;
                replaceAfterpayTitle();
                for (const node of mutation.addedNodes) {
                    if (node.nodeType !== Node.ELEMENT_NODE) continue;
                    const afterpayTemplate = node.id === 'afterpay-hyva-template'
                        ? node
                        : node.querySelector?.('#afterpay-hyva-template');
                    if (afterpayTemplate) {
                        // Afterpay template just appeared — it is now the selected method.
                        onPaymentMethodSelect('afterpay');
                        scheduleOrderTotalRefresh();
                        break;
                    }
                }
            }
        });

        const paymentMethodsList = document.getElementById('payment-method-list');
        if (paymentMethodsList) {
            observer.observe(paymentMethodsList, {childList: true, subtree: true});
        }

        // Broader observer: watches the whole document for .btn-place-order being
        // added or having its class reset by Magewire, and immediately re-hides it
        // when Afterpay is the active method.
        const btnObserver = new MutationObserver((mutations) => {
            if (currentPaymentMethod !== 'afterpay') return;
            for (const mutation of mutations) {
                // Node added — check if it is or contains the button.
                for (const node of mutation.addedNodes) {
                    if (node.nodeType !== Node.ELEMENT_NODE) continue;
                    const btn = node.classList?.contains('btn-place-order')
                        ? node
                        : node.querySelector?.('.btn-place-order');
                    if (btn) { btn.classList.add('hidden'); }
                }
                // Attribute change — button's class was reset externally.
                if (
                    mutation.type === 'attributes' &&
                    mutation.target.classList?.contains('btn-place-order') &&
                    !mutation.target.classList.contains('hidden')
                ) {
                    mutation.target.classList.add('hidden');
                }
            }
        });
        btnObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
