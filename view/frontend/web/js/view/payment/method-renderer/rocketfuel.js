define(
    [
        'Magento_Checkout/js/view/payment/default', "Magento_Checkout/js/model/quote",
    ],
    function (Component) {
        //'use strict';
        return Component.extend({
            defaults: {
                template: 'RKFL_Rocketfuel/payment/rocketfuel'
            }
        });
    }
);

