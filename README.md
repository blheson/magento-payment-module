# RocketFuel - RocketFuel Payment Method for Magento 2
RocketFuel Payment Method for Magento 2 version 0.2.4

The registration procedure is described in the [documentation RocketFuel](https://dev.rocketdemo.net/help)

# RocketFuel integration.

##Installation

    composer require "rkfl/module-rocketfuel-payment-magento2

    bin/magento module:enable RKF:_Rocketfuel

    php bin/magento setup:upgrade

##Configuration
- Go to  https://<your_shop_url>>/<your_admin_path>/admin/system_config/
- go to sales -> payment methods
- paste your merchant id
- paste your maerchant public key
- set environment
- callback url should be stored in your rocketfuel merchant settings
