'use strict';

window.addEventListener("load", () => {
    const initExpressCheckout = () => {
        return {
            countryCode: window?.afterpayLocaleCode ? window.afterpayLocaleCode : "US",
            enableForPDP: false,
            isLoading: true,
            trigger: "afterpay-button-pdp",
            minPrice: 0,
            maxPrice: 1000,
            priceSelector: ".product-info-main .price-final_price .price-wrapper .price",
            shippingOptionRequired: true,
            isProductAllowed: false,
            afterpayCartSubtotal: 0,
            ecButtonPlace: document.querySelector("#product_addtocart_form"),
            wrapElement: document.querySelector("#headless-afterpay-pdp-ec"),
            configData: '',

            init() {
                document.addEventListener('showHeadlessEC', (event) => {
                    this.extractSectionData(event.detail.afterpayConfig);
                    this.configData = event.detail.afterpayConfig;
                });
            },

            extractSectionData(data) {
                this.isLoading = false;

                this.ecButtonPlace = data?.placement_after_selector &&
                data?.placement_after_selector_bundle &&
                data?.product_type !== "bundle" ?
                    document.querySelector(data.placement_after_selector) :
                    document.querySelector(data.placement_after_selector_bundle);

                if (data) {
                    this.setCurrentData(data);
                }

                if (this.ecButtonPlace) {
                    if (document.querySelector('#afterpay-cta-pdp')) {
                        this.ecButtonPlace = document.querySelector('#afterpay-cta-pdp');
                    }

                    this.validateShowButton();
                    const afterpaySection = document.querySelector('.headless-afterpay-pdp-ec');
                    this.ecButtonPlace.insertAdjacentElement('afterend', afterpaySection);

                    // Add click event listener to the button
                    const afterpayButton = document.querySelector('.afterpay-express-pdp-button');
                    if (afterpayButton) {
                        afterpayButton.addEventListener('click', (event) => this.ecValidationAddToCart(event));
                    }
                }
            },

            setCurrentData (data) {
                this.shippingOptionRequired = data.product_type !== "virtual" && data.product_type !== "downloadable";
                this.minPrice = data.min_amount ? +data.min_amount : this.minPrice;
                this.maxPrice = data.max_amount ? +data.max_amount : this.maxPrice;
                this.enableForPDP = (data.is_enabled && data.is_enabled_ec_pdp_headless) ?? this.enableForPDP;
                this.isProductAllowed = data.is_product_allowed ?? this.isProductAllowed;
                this.afterpayCartSubtotal = this.checkCurrentSubtotal();
                this.priceSelector = data?.product_type != "bundle" ? data?.price_selector : data?.price_selector_bundle;
                let element = this.priceSelector ? document.querySelector(this.priceSelector).closest(".price-wrapper") : '';
                this.trackPriceChanges(element);
                this.checkPriceLimit(this.afterpayCartSubtotal);
            },

            checkCurrentSubtotal () {
                let currentCartData = JSON.parse(localStorage.getItem("mage-cache-storage"))?.cart;

                if(currentCartData && currentCartData?.subtotalAmount) {
                    return +currentCartData?.subtotalAmount;
                }

                return 0;
            },

            validateShowButton() {
                let currentPrice = this.getCurrentPrice();

                if (this.enableForPDP && this.isProductAllowed && +currentPrice >= +this.minPrice && +currentPrice <= +this.maxPrice) {
                    this.wrapElement.classList.remove("hidden");
                } else {
                    this.wrapElement.classList.add("hidden");
                }
            },

            getCurrentPrice() {
                let currentPrice = document.querySelector(this.priceSelector).textContent;
                currentPrice = currentPrice.replace(/[^\d.]/g, '');

                return currentPrice;
            },

            getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                let form_key = "";

                if (parts.length === 2) {
                    form_key = parts.pop().split(';').shift();
                } else {
                    if (parts.length > 2)
                        form_key = parts[1].split(';')[0]
                }

                return form_key;
            },

            addToCart(isValid) {
                const postUrl = `${BASE_URL}checkout/cart/add/`;
                const form = document.forms.product_addtocart_form;

                if (!isValid) return;

                const formData = new FormData(form);
                formData.append('form_key', this.getCookie('form_key'));

                this.isLoading = true;

                window.fetch(postUrl, {
                    body: formData,
                    method: 'POST'
                })
                .catch(console.error)
                .finally(() => {
                    addEventListener('private-content-loaded', event => {
                        if (this.checkPriceLimit(event.detail.data.cart)) {
                            document.getElementById(this.trigger).click();
                        }
                    });
            
                    dispatchEvent(new Event('reload-customer-section-data'));
                    this.initAfterpay();
                    document.getElementById(this.trigger).click();
                });
            },

            ecValidationAddToCart(event) {
                event.stopImmediatePropagation();

                const form = document.forms.product_addtocart_form;
                let isValid = form?.reportValidity();

                if (form && typeof (require) != "undefined") {
                    require([
                        'jquery',
                        'mage/mage'
                    ], function ($) {

                        let dataForm = $('#product_addtocart_form'),
                            isValid = false;

                        if (dataForm.valid()) {
                            isValid = true;
                        }

                        const event = new CustomEvent('ecFormValid', {detail: {isValid: isValid}});
                        document.dispatchEvent(event);

                    });

                    document.addEventListener('ecFormValid', (event) => {
                        if (event?.detail?.isValid) {
                            if (this.configData?.product_type == "bundle") {
                                this.initAfterpay();
                                setTimeout(() => {
                                    document.getElementById(this.trigger).click();
                                  }, 1000);
                            }else{
                                this.addToCart(event.detail.isValid);
                            }
                        }
                    });
                } else {
                    if (form || form?.reportValidity()) {
                        this.addToCart(isValid);
                    }
                }
            },

            trackPriceChanges(element) {
                if(!element) return;

                const targetNode = element,
                callback = (mutationsList, observer) => {
                    for (const mutation of mutationsList) {
                        if (mutation.type === 'characterData' || mutation.type === 'childList') {
                            this.validateShowButton();
                        }
                    }
                };

                const observer = new MutationObserver(callback),
                    config = { 
                        characterData: true, 
                        childList: true, 
                        subtree: true 
                    };

                observer.observe(targetNode, config);
            },

            checkPriceLimit(cartSubtotal) {
                let total = cartSubtotal?.subtotalAmount ? cartSubtotal?.subtotalAmount : cartSubtotal,
                    currentPrice = this.getCurrentPrice();

                if (+currentPrice >= +this.minPrice && +total <= this.maxPrice) {
                    this.wrapElement.classList.remove("hidden");
                    return true;
                } else {
                    this.wrapElement.classList.add("hidden");
                }

                this.isLoading = false;
                return false;
            },

            objectToUrlEncoded(obj) {
                return new URLSearchParams(obj).toString();
            },

            getShippingOptions(shippingAddress, actions) {
                shippingAddress = this.objectToUrlEncoded(shippingAddress);

                this.fetchData("afterpay/express/getShippingOptions", shippingAddress)
                    .then(response => {
                        if (response?.shippingOptions) {
                            return actions.resolve(response.shippingOptions);
                        } else {
                            AfterPay.close();
                            return actions.reject(Square.Marketplace.constants.SHIPPING_ADDRESS_UNSUPPORTED);
                        }
                    });
            },

            onComplete(event) {
                if (event.data.status === 'CANCELLED') {
                    localStorage?.removeItem('mage-cache-storage');
                    window.location.reload();
                }

                this.placeOrder(event);
            },

            handleMessage(type, text) {
                if (typeof (dispatchMessages) != "undefined") {
                    dispatchMessages([{type, text}], 5000);
                }
            },

            placeOrder(event) {
                const data = this.objectToUrlEncoded(event.data);

                this.isLoading = true;

                this.fetchData("afterpay/express/placeOrder", data)
                    .then(response => {
                        if(response?.error) {
                            let messages = [
                                {
                                    text: response?.error,
                                    type: 'error'
                                }
                            ],
                            messagesJson = JSON.stringify(messages);

                            cookieStore.set('mage-messages', messagesJson);
                            window.location.href = response.redirectUrl;
                        }else{
                            if (response?.redirectUrl) {
                                localStorage?.removeItem('mage-cache-storage');
                                localStorage?.removeItem('messages');
                                window.mageMessages = [];
                                window.location.href = response.redirectUrl;
                                this.isLoading = false;
                            }
                        }
                    });
            },

            getAfterpayToken(actions) {
                this.fetchData("afterpay/express/createCheckout")
                    .then(response => {
                        if (response?.afterpay_token) {
                            return actions.resolve(response.afterpay_token);
                        } else {
                            AfterPay.close();
                            return actions.reject(Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                        }
                    });
            },

            fetchData(url = "", data = "") {
                const postUrl = `${BASE_URL}${url}`;

                this.isLoading = true;

                return window.fetch(postUrl, {
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: data,
                    method: 'POST',
                    dataType: 'json'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        return data;
                    })
                    .catch(error => {
                        console.error('There was a problem with the fetch operation:', error);
                    });
            },

            checkProductInCart () {
                let cartItems = JSON.parse(localStorage.getItem("mage-cache-storage"))?.cart?.items,
                    hasVirtual = false,
                    hasSimple = false;

                if(cartItems?.length > 0) {
                    cartItems.forEach((item, index) => {
                        if(item.product_type == "virtual" || item.product_type == "downloadable") {
                            hasVirtual = true;
                        }else {
                            hasSimple = true; 
                        }
                    });
                }

                if(hasVirtual && hasSimple) {
                    this.shippingOptionRequired = true;
                }

                if(hasVirtual && hasSimple == false) {
                    if(this.configData.product_type !== "virtual" && this.configData.product_type !== "downloadable") {
                        this.shippingOptionRequired = true;
                    }
                }

                if(hasVirtual == false && hasSimple) {
                    if(this.configData.product_type == "virtual" || this.configData.product_type == "downloadable") {
                        this.shippingOptionRequired = true;
                    }
                }
            },

            initAfterpay() {
                this.checkProductInCart();

                AfterPay.initializeForPopup({
                    countryCode: this.countryCode.toLocaleUpperCase(),
                    buyNow: true,
                    shippingOptionRequired: this.shippingOptionRequired,
                    pickup: false,
                    target: "#" + this.trigger,
                    onCommenceCheckout: actions => this.getAfterpayToken(actions),
                    onComplete: event => this.onComplete(event),
                    onShippingAddressChange: (shippingAddress, actions) => this.getShippingOptions(shippingAddress, actions)
                });
            }
        };
    };

    window.expressCheckout = initExpressCheckout();
    window.expressCheckout.init();
});
