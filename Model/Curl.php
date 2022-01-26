<?php

namespace Rocketfuel\Rocketfuel\Model;

use Magento\Framework\HTTP\Client\Curl as MagentoCurl;


class Curl
{
    protected $curl;
    /**
     * constructor.
     * @param MagentoCurl $curl
     */
    public function __construct(MagentoCurl $curl){

        $this->curl = $curl;
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("Content-Length", 200);

    }
    /**
     * Process data to get uuid
     *
     * @param array $data - Data from plugin.
     */
    public function processPayment($data){

        $response = self::auth($data);

        if (is_wp_error($response)) {

            return rest_ensure_response($response);
        }

        $response_code = wp_remote_retrieve_response_code($response);

        $response_body = wp_remote_retrieve_body($response);

        $result = json_decode($response_body);
        if ($response_code != '200') {
            wc_add_notice(__('Authorization cannot be completed', 'rocketfuel-payment-gateway'), 'error');
            return false;
        }

        $charge_response = self::createCharge($result->result->access, $data);
        $charge_response_code = wp_remote_retrieve_response_code($charge_response);
        $wp_remote_retrieve_body = wp_remote_retrieve_body($charge_response);

        if ($charge_response_code != '200') {
            wc_add_notice(__('Could not establish an order', 'rocketfuel-payment-gateway'), 'error');
            return false;
        }

        return $wp_remote_retrieve_body;
    }
    /**
     * Process authentication
     * @param array $data
     */
    public function auth($cred){
        $body = json_encode($cred);
        $url = $data['endpoint'] . '/auth/login';
        $response = $this->curl->post($url, $body);

        return $response;
    }
    /**
     * Get UUID of the customer
     * @param array $data
     */
    public function createCharge($accessToken, $data)
    {
        $body = json_encode($data['body']);

        $this->curl->addHeader('authorization', "Bearer  $accessToken");

        $url = $data['endpoint'] . '/hosted-page';

        $response =  $this->curl->post($url, $body );

        return $response;
    }
}
