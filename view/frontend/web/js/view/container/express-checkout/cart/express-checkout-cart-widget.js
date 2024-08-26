window.addEventListener("load", () => {
    const initExpressCheckout = () => {
        return {
            countryCode: window?.afterpayLocaleCode ? window.afterpayLocaleCode : "US",
            enableForCart: false,
            isLoading: true,
            trigger: "afterpay-button-cart",
            minPrice: 0,
            maxPrice: 1000,
            shippingOptionRequired: true,
            isProductAllowed: false,
            afterpayCartSubtotal: 0,
            ecButtonPlace: document.querySelector(".cart-container .cart-totals"),
            wrapElement: document.querySelector("#headless-afterpay-cart-ec"),
            isVirtual: false,

            init() {
                let self = this;
                document.addEventListener('showHeadlessCart', (event) => {
                    setTimeout(() => {
                        self.extractSectionData(event.detail.afterpayConfig);
                    }, 1000);
                });

                document.addEventListener("click", function(e){
                    if(e.target.classList.contains('update')) {
                        setTimeout(function() {
                            document.dispatchEvent(window.reloadCartPage);
                        }, 1000);
                    }
                });
            },

            extractSectionData(data) {
                let self = this;
                this.isLoading = false;

                this.ecButtonPlace = data?.placement_after_selector
                    ? document.querySelector(data.placement_after_selector)
                    : this.ecButtonPlace;

                if (data) {
                    this.setCurrentData(data);
                }

                if (this.ecButtonPlace) {
                    if (document.querySelector('#afterpay-cta-cart')) {
                        this.ecButtonPlace = document.querySelector('#afterpay-cta-cart');
                    }
                    let afterpaySection = document.querySelector('.headless-afterpay-cart-ec');
                    if(!afterpaySection) {
                        afterpaySection = self.wrapElement;
                    }
                    this.ecButtonPlace.insertAdjacentElement('afterend', afterpaySection);

                    this.initAfterpay();

                    // Add click event listener to the button
                    const afterpayButton = document.querySelector('.afterpay-express-button-cart');
                    if (afterpayButton) {
                        afterpayButton.addEventListener('click', (event) => this.ecValidationAddToCart(event));
                    }

                    this.validateShowButton(this.checkPriceLimit());
                }
            },

            setCurrentData (data) {
                this.enableForCart = (data.is_enabled && data.is_enabled_ec_cart_page_headless) ?? this.enableForPDP;
                this.isProductAllowed = data.is_product_allowed ?? this.isProductAllowed;
                this.afterpayCartSubtotal = this.checkCurrentSubtotal();
                this.minPrice = data.min_amount ? +data.min_amount : this.minPrice;
                this.maxPrice = data.max_amount ? +data.max_amount : this.maxPrice;
                this.isVirtual = data.is_virtual ? data.is_virtual : this.isVirtual;
                this.shippingOptionRequired = !this.isVirtual;
            },

            checkCurrentSubtotal () {
                let currentCartData = JSON.parse(localStorage.getItem("mage-cache-storage")).cart;

                if(currentCartData && currentCartData?.subtotalAmount) {
                    return +currentCartData?.subtotalAmount;
                }

                return 0;
            },

            validateShowButton(priceIsValid = false) {
                if (this.enableForCart && this.isProductAllowed && priceIsValid) {
                    this.wrapElement.classList.remove("hidden");
                } else {
                    this.wrapElement.classList.add("hidden");
                }
            },

            ecValidationAddToCart(event) {
                this.initAfterpay();
                document.getElementById(this.trigger).click();
            },

            getCurrentSubtotal () {
                let currentCartData = JSON.parse(localStorage.getItem("mage-cache-storage"))?.cart;

                if(currentCartData && currentCartData?.subtotalAmount) {
                    return +currentCartData?.subtotalAmount;
                }

                return 0;
            },

            checkPriceLimit() {
                let total = this.getCurrentSubtotal();

                return +total >= this.minPrice && +total <= this.maxPrice;
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

            initAfterpay() {
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
