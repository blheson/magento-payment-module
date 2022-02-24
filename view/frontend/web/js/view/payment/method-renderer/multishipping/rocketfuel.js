/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([

    'RKFL_Rocketfuel/js/view/payment/method-renderer/rocketfuel',

], function (
    Component
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'RKFL_Rocketfuel/payment/multishipping/rocketfuel',
        }
    });
});
