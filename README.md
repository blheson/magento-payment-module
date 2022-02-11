# RocketFuel - RocketFuel Payment Method for Magento 2
RocketFuel Payment Method for Magento 2 version 2.0.0

The registration procedure is described in the [documentation RocketFuel](https://dev.rocketdemo.net/help)

# RocketFuel integration.

##Installation

    composer require rkfl/module-rocketfuel-payment-magento2

    php bin/magento module:enable RKFL_Rocketfuel

    php bin/magento setup:upgrade

##Configuration
- Go to  https://<your_shop_url>>/<your_admin_path>/admin/system_config/
- Go to sales -> payment methods
- Paste your merchant id
- Paste your maerchant public key
- Set environment
- Callback url should be stored in your rocketfuel merchant settings
