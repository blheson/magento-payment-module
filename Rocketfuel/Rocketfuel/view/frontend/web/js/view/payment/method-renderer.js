define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        //'use strict';
        rendererList.push(
            {
                type: 'rocketfuel',
                component: 'Rocketfuel_Rocketfuel/js/view/payment/method-renderer/rocketfuel'
            }
        );
        return Component.extend({});
    }
);

/*


*/
