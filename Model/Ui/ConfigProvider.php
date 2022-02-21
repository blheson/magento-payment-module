<?php
namespace Rkfl\Rocketfuel\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\Store as Store;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{

    protected $method;

    public function __construct(PaymentHelper $paymentHelper, Store $store)
    {
        $this->method = $paymentHelper->getMethodInstance(\Rkfl\Rocketfuel\Model\PaymentMethod::CODE);
        $this->store = $store;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $publicKey = $this->method->getConfigData('rocketfuel_merchant_public_key');


        return [
            'payment' => [
                \Rkfl\Rocketfuel\Model\PaymentMethod::CODE => [
                    'public_key' => $publicKey,
                    // 'integration_type' => $integrationType,
                    // 'api_url' => $this->store->getBaseUrl() . 'rest/',
                    // 'integration_type_standard_url' => $this->store->getBaseUrl() . 'paystack/payment/setup',
                    // 'recreate_quote_url' => $this->store->getBaseUrl() . 'paystack/payment/recreate',
                ]
            ]
        ];
    }
    
    public function getStore() {
        return $this->store;
    }

    
    
}
