<?php


namespace Rocketfuel\Rocketfuel\Model;


class Rocketfuel
{
    const CRYPT_ALGO = 'SHA256';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * get crypt algo
     *
     * @return string
     */
    public function getCryptAlgo()
    {
        return self::CRYPT_ALGO;
    }

    /**
     *  get merchant id from settings
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_merchant_id');
    }

    /**
     *  get merchant public key from settings
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
            'dev' => 'https://dev-app.rocketdemo.net/api',
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
     *  get iframe url
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
     *  get iframe url
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_merchant_password');
    }

    /**
     *  get serialized payload from order
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
                'id' => bin2hex( random_bytes(20)),
                'price' => $order->getShippingAmount(),
                'name' => 'Shipping: ' . $order->getShippingDescription(),
                'quantity' => 1
            ];
        }

        $out['amount'] = $order->getGrandTotal();
         $out['currency'] = "USD";
        $out['merchant_id'] = $this->getMerchantId();
        $out['order'] = $order->getId();
        $out['redirectUrl'] = '';

        return $this->sortPayload($out);
    }

    /**
     * custom serialize array
     *
     * @param $payload
     * @return array
     */
    protected function sortPayload($payload)
    {
        $sorted = [];
        if (is_object($payload))
            $payload = (array)$payload;
        $keys = array_keys($payload);
        sort($keys);

        foreach ($keys as $key)
            $sorted[$key] = is_array($payload[$key]) ? $this->sortPayload($payload[$key]) : (string)$payload[$key];

        return $sorted;
    }
    /**
     * custom serialize array
     *
     * @param $payload
     * @return array
     */
    public function getEnvironment()
    {
        return $this->scopeConfig->getValue('payment/rocketfuel/rocketfuel_environment');
    }
    public function merchantAuth()
	{
		return $this->getEncrypted($this->getMerchantId());
	}

    	/**
	 * Encrypt Data
	 *
	 * @param $to_crypt string to encrypt
	 * @return string
	 */
    protected function getEncrypted($to_crypt)
    {

        $out = '';

        $cert = file_get_contents(dirname(__FILE__,2) . '/key/.rf_public.key');
        
        $public_key = openssl_pkey_get_public($cert);

        $key_lenght = openssl_pkey_get_details($public_key);

        $part_len = $key_lenght['bits'] / 8 - 11;
        $parts = str_split($to_crypt, $part_len);
        foreach ($parts as $part) {
            $encrypted_temp = '';
            openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
            $out .=  $encrypted_temp;
        }

        return base64_encode($out);
    }
}
