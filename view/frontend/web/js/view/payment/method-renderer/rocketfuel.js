define(
    [
        "jquery",
        'mage/url',
        "Magento_Checkout/js/view/payment/default",
        "Magento_Checkout/js/action/place-order",
        "Magento_Checkout/js/model/payment/additional-validators",
        "Magento_Checkout/js/model/quote",
        "Magento_Checkout/js/model/full-screen-loader",
        "Magento_Checkout/js/action/redirect-on-success",
    ],
    function ($,
        mageUrl,
        Component,
        placeOrderAction,
        additionalValidators,
        quote,
        fullScreenLoader,
        redirectOnSuccessAction) {

        'use strict';

        return Component.extend({
            defaults: {
                template: 'RKFL_Rocketfuel/payment/rocketfuel'
            },
            redirectAfterPlaceOrder: true,
            theObject: function () {
                return this;
            },
            placingOrder: false,
            placeOrderClicked: false,
            isActive: function () {
                return true;
            },
            placeOrder: async function (data, event) {
                var _this = this;

                if (_this.placeOrderClicked === true) return;
                // var self = this;
                console.count("I am called", _this.placeOrderClicked);

                _this.placeOrderClicked = true;

                if (event) {
                    event.preventDefault();
                }

                var checkoutConfig = window.checkoutConfig;
                var paymentData = quote.billingAddress();

                // fullScreenLoader.startLoader();

                if (checkoutConfig.isCustomerLoggedIn) {
                    var customerData = checkoutConfig.customerData;
                    paymentData.email = customerData.email;
                } else {
                    paymentData.email = quote.guestEmail;
                }

                //     var quoteId = checkoutConfig.quoteItemData[0].quote_id;

                try {
                    let results = await _this.init();

                    if (results === false) {

                        console.log('engineplacingOrder trturen to false');

                        _this.placingOrder = false;

                        _this.placeOrderClicked = false;

                    }

                    console.log('Response from init', results);

                    // return _this.customplaceOrder();

                    return results;

                } catch (error) {
                    _this.placingOrder = false;

                    _this.placeOrderClicked = false;
                    console.error("There was an error with the place order flow,", error?.message);

                }

            },

            updateOrder: function (result) {
                try {

                    console.log("Response from callback :", result);



                    let status = "wc-on-hold";

                    if (result?.status === undefined) {
                        return false;
                    }
                    fullScreenLoader.stopLoader();

                    let result_status = parseInt(result.status);

                    if (result_status === 101) {
                        status = "wc-partial-payment";
                    }

                    if (result_status === 1 || result.status === "completed") {

                        status = 'wc-processing';

                        //placeholder to get order status set by seller
                    }

                    if (result_status === -1) {
                        status = "wc-failed";
                    }


                } catch (error) {

                    console.error('Error from update order method', error);

                }

            },


            windowListener: function () {
                let engine = this;

                window.addEventListener('message', (event) => {

                    switch (event.data.type) {


                        case 'rocketfuel_new_height':

                            engine.watchIframeShow = false;

                        case 'rocketfuel_result_ok':


                            if (event.data.response) {
                                engine.updateOrder(event.data.response);
                            }

                        default:
                            break;
                    }

                })
            },
            /**
             *  @override
             */
            customOrders: async function () {


            },
            /**
             *  @override
             */
            afterPlaceOrders: async function () {


            },

            iframeData: async function () {

                // let url = document.querySelector('input[name=admin_url_rocketfuel]').value;
                let productTotal = 0;
                let cart = checkoutConfig.totalsData.items.map(item => {
                    productTotal += item.price;
                    return {
                        'name': item.name,
                        'price': item.price,
                        'quantity': item.qty,
                        'id': item.item_id.toString()
                    }
                });


                if (checkoutConfig.selectedShippingMethod?.amount || (checkoutConfig.totalsData.base_grand_total - productTotal > 0)) {
                    cart = [...cart, { name: 'Shipping', 'quantity': 1, price: checkoutConfig.selectedShippingMethod?.amount || productTotal, id: new Date().getTime().toString(), }];
                }


                let fd = new FormData();

                fd.append("currency", checkoutConfig.totalsData.base_currency_code);

                fd.append("amount", checkoutConfig.totalsData.base_grand_total);

                fd.append("cart", JSON.stringify(cart));



                let response = await fetch(window.location.origin + '/rest/V1/rocketfuel-post-uuid', {
                    method: 'post',
                    body: fd
                });

                let result = await response.json();

                let parsedJson = JSON.parse(result);
                // let parsedJson = result;


                if (!parsedJson.uuid) {
                    return false;
                }

                // console.log("res", result.data.result.uuid);
                return parsedJson;
                // return '85c25960-fa06-41c0-9210-ef38a141cbc4';

            },
            getUserData: function () {

                var checkoutConfig = window.checkoutConfig;

                var paymentData = quote.billingAddress();


                let user_data = {
                    first_name: paymentData?.firstname,
                    last_name: paymentData?.lastname,
                    email: paymentData?.email,
                }

                if (!user_data) return false;

                return user_data;

            },
            initRocketFuel: async function () {
                let _this = this;
                return new Promise(async (resolve, reject) => {

                    if (!RocketFuel) {
                        location.reload();
                        reject();
                    }

                    let userData = _this.getUserData();


                    let payload, response, rkflToken;

                    let iframeData = await this.iframeData();

                    if (!iframeData.env || !iframeData.uuid) {
                        console.log('Iframe data is not complete', iframeData);
                        return;
                    }
                    _this.rkfl = new RocketFuel({
                        environment: iframeData.env
                    });

                    //set uuid
                    // let resultAUTH = '{uuid: "502a308c-b19d-414f-a5e3-15429b41f035", merchantAuth: "CDYFO3q4wTrqgOK/afdVveZ4lQj+9kCRdNHcg9kKM0LcDeFUKC…3o57phFDjmb0TPICoM2Teq2awFJN6BTXEJ6bvot98FDsULQ==", env: "stage2", temporary-order-id: "9980e7e3c6777a6382ffc2041936ce46-71fdcdaef0"}';
                    _this.rkflConfig = {
                        uuid: iframeData.uuid,
                        callback: _this.updateOrder,
                        environment: iframeData.env
                    }

                    if (userData.first_name && userData.email) {

                        payload = {
                            firstName: userData.first_name,
                            lastName: userData.last_name,
                            email: userData.email,
                            merchantAuth: iframeData.merchantAuth,
                            kycType: 'null',
                            kycDetails: {
                                'DOB': "01-01-1990"
                            }
                        }

                        try {


                            if (userData.email !== localStorage.getItem('rkfl_email')) { //remove signon details when email is different
                                localStorage.removeItem('rkfl_token');
                                localStorage.removeItem('access');

                            }

                            rkflToken = localStorage.getItem('rkfl_token');

                            if (!rkflToken && payload.merchantAuth) {

                                response = await _this.rkfl.rkflAutoSignUp(payload, iframeData.env);

                                localStorage.setItem('rkfl_email', userData.email);

                                if (response) {

                                    rkflToken = response.result?.rkflToken;

                                }

                            }


                            if (rkflToken) {
                                _this.rkflConfig.token = rkflToken;
                            }

                            resolve(true);

                        } catch (error) {

                            reject(error?.message);

                        }

                    }

                    if (_this.rkflConfig) {

                        _this.rkfl = new RocketFuel(_this.rkflConfig); // init RKFL

                        resolve(true);

                    } else {
                        resolve(false);
                    }

                })

            },
            init: async function () {

                let engine = this;

                return new Promise(async (resolve, reject) => {
                    try {
                        if (engine.isPlaceOrderActionAllowed() === false) return;
                        let res = await engine.initRocketFuel();

                      
                        engine.isPlaceOrderActionAllowed(false);
                        window.addEventListener('message', (event) => {
                            switch (event.data.type) {
                                case 'rocketfuel_iframe_close':
                                    console.log('Status of placing order', engine.placingOrder, event.data.paymentCompleted);

                                    if (engine.placingOrder === true) return;
                                    if (event.data.paymentCompleted === 1) {

                                        console.log('This is closed and also validating');
                                        engine.placingOrder = true;
                                        // engine.isPlaceOrderActionAllowed(false);

                                        if (additionalValidators.validate()
                                        ) {


                                            engine.getPlaceOrderDeferredObject()
                                                .done(
                                                    function () {
                                                        // self.afterPlaceOrder();

                                                        // if (self.redirectAfterPlaceOrder) {
                                                        redirectOnSuccessAction.execute();
                                                        // }
                                                    }
                                                ).always(
                                                    function () {
                                                        console.log('always')
                                                        engine.isPlaceOrderActionAllowed(true);

                                                    }
                                                );

                                            resolve(true);
                                            return;
                                        }
                                    } else {
                                        fullScreenLoader.stopLoader();
                                        engine.isPlaceOrderActionAllowed(true);

                                        resolve(false);
                                    }



                            }


                        })
                    } catch (error) {

                        console.log('error from promise', error);
                        resolve(false)

                    }

                    console.log('Done initiating RKFL');

                    engine.windowListener();

                    engine.startPayment();
                })


            },
            startPayment: function () {
                let engine = this;
                this.watchIframeShow = true;

                let checkIframe = setInterval(() => {

                    if (engine.rkfl.iframeInfo.iframe) {
                        engine.rkfl.initPayment();
                        clearInterval(checkIframe);
                    }

                }, 500);

            },
        });
    }
);

console.log('Shwoer 5');