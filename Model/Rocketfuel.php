<?php


namespace RKFL\Rocketfuel\Model;



class Rocketfuel
{
    protected const CRYPT_ALGO = 'SHA256';

    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
    }

    /**
     * Get crypt algo
     *
     * @return string
     */
    public function getCryptAlgo()
    {
        return self::CRYPT_ALGO;
    }

    /**
     *  Get merchant id from settings
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_merchant_id');
    }

    /**
     *  Get merchant public key from settings
     *
     * @return string
     */
    public function getMerchantPublicKey()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_merchant_public_key');
    }
    /**
     * Get Endpoint
     */
    public function getEndpoint()
    {
        $environment = $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_environment');

        $environmentData = array(
            'prod' => 'https://app.rocketfuelblockchain.com/api',
            'sandbox' => 'https://app-sandbox.rocketfuelblockchain.com/api',
            'stage2' => 'https://qa-app.rocketdemo.net/api',
            'preprod' => 'https://preprod-app.rocketdemo.net/api',
        );

        return isset($environmentData[$environment]) ? $environmentData[$environment] : 'https://app.rocketfuelblockchain.com/api';
    }
    /**
     * Get Endpoint
     */
    public function updateOrderUrl()
    {
        return '/rest/V1/update-order';
    }

    /**
     *  Get iframe url
     *
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_iframe_url');
    }

    /**
     *  Get Email url
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_merchant_email');
    }
    /**
     *  Get iframe url
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_merchant_password');
    }

    /**
     *  Get serialized payload from order
     *
     * @param $order
     * @return array
     */
    public function getOrderPayload($order)
    {
        $out = [];
        foreach ($order->getAllItems() as $product) {
            $out['cart'][] = [
                'id' => $product->getId(),
                'price' => $product->getPrice(),
                'name' => $product->getName(),
                'quantity' => $product->getQtyOrdered()
            ];
        };

        if ($order->getShippingAmount()) {
            $out['cart'][] = [
                'id' => bin2hex(random_bytes(20)),
                'price' => $order->getShippingAmount(),
                'name' => 'Shipping: ' . $order->getShippingDescription(),
                'quantity' => 1
            ];
        }

        $out['amount'] = $order->getGrandTotal();
        $out['currency'] = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        $out['merchant_id'] = $this->getMerchantId();
        $out['order'] = $order->getId();
        $out['redirectUrl'] = '';

        return $this->sortPayload($out);
    }

    /**
     * Custom serialize array
     *
     * @param $payload
     * @return array
     */
    protected function sortPayload($payload)
    {
        $sorted = [];

        if (is_object($payload)) {
            $payload = (array)$payload;
        }

        $keys = array_keys($payload);

        sort($keys);

        foreach ($keys as $key) {
            $sorted[$key] = is_array($payload[$key]) ? $this->sortPayload($payload[$key]) : (string)$payload[$key];
        }

        return $sorted;
    }
    /**
     * Custom serialize array
     *
     * @param $payload
     * @return array
     */
    public function getEnvironment()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_environment');
    }
    /**
     * Get Merchant Auth
     * @return string
     */
    public function merchantAuth()
    {
        return $this->getEncrypted($this->getMerchantId());
    }

    /**
     * Encrypt Data
     *
     * @param $toCrypt string to encrypt
     * @return string
     */
    public function getEncrypted($toCrypt, $usePublicKey = true)
    {

        $out = '';

        if ($usePublicKey) {

            $cert = file_get_contents(dirname(__FILE__, 2) . '/key/.rf_public.key');

        } else {

            $cert  = $this->getMerchantPublicKey();
        }

        $public_key = openssl_pkey_get_public($cert);

        $key_lenght = openssl_pkey_get_details($public_key);

        $part_len = $key_lenght['bits'] / 8 - 11;

        $parts = str_split($toCrypt, $part_len);

        foreach ($parts as $part) {
            $encrypted_temp = '';

            openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);

            $out .=  $encrypted_temp;
        }

        return base64_encode($out);
    }
}
