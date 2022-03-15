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
            redirectAfterPlaceOrder: false,
            isActive: function () {
                return true;
            },
            placeOrder: async function () {
                var checkoutConfig = window.checkoutConfig;
                var paymentData = quote.billingAddress();


                // var paystackConfiguration = checkoutConfig.payment.pstk_paystack;

                // if (paystackConfiguration.integration_type == 'standard') {
                //     this.redirectToCustomAction(paystackConfiguration.integration_type_standard_url);
                // } else {
                if (checkoutConfig.isCustomerLoggedIn) {
                    var customerData = checkoutConfig.customerData;
                    paymentData.email = customerData.email;
                } else {
                    paymentData.email = quote.guestEmail;
                }



                console.log(paymentData);
                //     var quoteId = checkoutConfig.quoteItemData[0].quote_id;

                var _this = this;
                _this.isPlaceOrderActionAllowed(false);
                let results = await _this.init();
                console.log('init', results);
            },
            /**
                * Provide redirect to page
                */
            redirectToCustomAction: function (url) {
                fullScreenLoader.startLoader();
                window.location.replace(mageUrl.build(url));
            },
            getEnvironment: function () {
                var checkoutConfig = window.checkoutConfig;

                let environment;
                return environment || 'stage2';
                // return environment || 'prod';
            }, updateOrder: function (result) {
                try {

                    console.log("Response from callback :", result);

                    console.log("order_id :", this.order_id);

                    let status = "wc-on-hold";

                    if (result?.status === undefined) {
                        return false;
                    }

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
                        case 'rocketfuel_iframe_close':

                            break;
                        case 'rocketfuel_new_height':

                            engine.watchIframeShow = false;

                        case 'rocketfuel_result_ok':

                            console.log('Event from rocketfuel_result_ok', event.data.response);

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
            afterPlaceOrder: function () {


                // var checkoutConfig = window.checkoutConfig;
                // var paymentData = quote.billingAddress();


                // // var paystackConfiguration = checkoutConfig.payment.pstk_paystack;

                // // if (paystackConfiguration.integration_type == 'standard') {
                // //     this.redirectToCustomAction(paystackConfiguration.integration_type_standard_url);
                // // } else {
                // if (checkoutConfig.isCustomerLoggedIn) {
                //     var customerData = checkoutConfig.customerData;
                //     paymentData.email = customerData.email;
                // } else {
                //     paymentData.email = quote.guestEmail;
                // }



                // console.log(paymentData);
                // //     var quoteId = checkoutConfig.quoteItemData[0].quote_id;

                // var _this = this;
                // _this.isPlaceOrderActionAllowed(false);


            },

            getUUID: async function () {

                // let url = document.querySelector('input[name=admin_url_rocketfuel]').value;

                let cart = checkoutConfig.totalsData.items.map(item => {
                    return {
                        'name': item.name,
                        'price': item.price,
                        'quantity': item.qty,
                        'item': item.item_id
                    }
                });

                let data = JSON.stringify({
                    'currency': checkoutConfig.totalsData.base_currency_code,
                    'amount': checkoutConfig.totalsData.base_grand_total,
                    cart
                });
                let fd = new FormData();
                
                fd.append("data", data);

                let response = await fetch('/V1/rocketfuel-get-uuid?data='+data);

                console.log('the response', response);

                // if (!response.ok) {
                //     return false;
                // }

                // let result = await response.json();

                // if (!result.data?.result?.uuid) {

                //     return false;

                // }



                // RocketfuelPaymentEngine.order_id =  (new Date()).getTime()+Math.floor((Math.random()*9999));

                // console.log("res", result.data.result.uuid);

                // return result.data.result.uuid;
                return '85c25960-fa06-41c0-9210-ef38a141cbc4';

            },
            getUserData: function () {

                var checkoutConfig = window.checkoutConfig;

                var paymentData = quote.billingAddress();


                let user_data = {
                    first_name: paymentData?.firstname,
                    last_name: paymentData?.lastname,
                    email: paymentData?.email,
                    merchant_auth: null
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

                    console.log('user data', userData);

                    let payload, response, rkflToken;

                    _this.rkfl = new RocketFuel({
                        environment: _this.getEnvironment()
                    });

                    let uuid = await this.getUUID(); //set uuid

                    _this.rkflConfig = {
                        uuid,
                        callback: _this.updateOrder,
                        environment: _this.getEnvironment()
                    }

                    if (userData.first_name && userData.email) {

                        payload = {
                            firstName: userData.first_name,
                            lastName: userData.last_name,
                            email: userData.email,
                            merchantAuth: userData.merchant_auth,
                            kycType: 'null',
                            kycDetails: {
                                'DOB': "01-01-1990"
                            }
                        }

                        try {
                            console.log('details', userData.email, localStorage.getItem('rkfl_email'), payload);

                            if (userData.email !== localStorage.getItem('rkfl_email')) { //remove signon details when email is different
                                localStorage.removeItem('rkfl_token');
                                localStorage.removeItem('access');

                            }

                            rkflToken = localStorage.getItem('rkfl_token');

                            if (!rkflToken && payload.merchantAuth) {

                                response = await _this.rkfl.rkflAutoSignUp(payload, _this.getEnvironment());

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
                        console.log('data', _this.rkflConfig);
                        _this.rkfl = new RocketFuel(_this.rkflConfig); // init RKFL

                        resolve(true);

                    } else {
                        resolve(false);
                    }

                })

            },
            init: async function () {

                let engine = this;
                console.log('Start initiating RKFL');

                try {

                    let res = await engine.initRocketFuel();
                    console.log(res);

                } catch (error) {

                    console.log('error from promise', error);

                }

                console.log('Done initiating RKFL');

                engine.windowListener();

                engine.startPayment();

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

console.log('shoswn');