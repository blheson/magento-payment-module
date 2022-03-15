<?php

namespace RKFL\Rocketfuel\Model;

use Magento\Framework\HTTP\Client\Curl as MagentoCurl;


class Curl
{
    protected $curl;
    /**
     * constructor.
     * @param MagentoCurl $curl
     */
    public function __construct(MagentoCurl $curl)
    {

        $this->curl = $curl;
        $this->curl->addHeader("Content-Type", "application/json");
        // $this->curl->addHeader("Content-Length", 200);

    }
    /**
     * Process data to get uuid
     *
     * @param array $data - Data from plugin.
     */
    public function processPayment($data)
    {

        $response = $this->auth($data);

        $result = json_decode($response);
      
        if (!isset( $result->ok) || $result->ok !== true || !$result->result->access ) {

            return array(
                'success' => false, 
                'message' => 'Authorization cannot be completed'
            );
        }

      
        if (!$result) {

            return array(
                'success' => false, 
                'message' => 'Authorization cannot be completed'
            );
        }
  
        $charge_response = $this->createCharge($result->result->access, $data);

        $charge_result = json_decode( $charge_response);

        if (!$charge_result || $charge_result->ok === false) {

            return array('success' => false, 'message' => 'Could not establish an order: '.$charge_result->message);
        }

        return $charge_result;
    }

    /**
     * Process authentication
     * @param array $data
     */
    public function auth($data)
    {
        $body = json_encode($data['cred']);

        $url = $data['endpoint'] . '/auth/login';


        $this->curl->post($url, $body);

        $result = $this->curl->getBody();
        
        return $result;
    }

    /**
     * Get UUID of the customer
     * @param string $accessToken
     * @param array $data
     */
    public function createCharge($accessToken, $data)
    {

        $body = $data['body'];

        $this->curl->addHeader('authorization', "Bearer  $accessToken");

        $url = $data['endpoint'] . '/hosted-page';
 
        $this->curl->post($url, json_encode($body));

        // output of curl request
        $result = $this->curl->getBody();
  
        return $result;
    }
}
