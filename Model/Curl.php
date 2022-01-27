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
        file_put_contents(__DIR__ . '/log.json', "\n" . 'From AUth: '."\n" . $response  . "\n", FILE_APPEND);
        $result = json_decode($response);
        if ($result->ok !== true || !$result->result->access ) {

            return false;
        }

      
        if (!$result) {

            return array(
                'success' => false, 
                'message' => 'Authorization cannot be completed'
            );
        }

        $charge_response = $this->createCharge($result->result->access, $data);

        file_put_contents(__DIR__ . '/log.json', "\n" . 'From createCharge: '."\n" . $charge_response . "\n", FILE_APPEND);
        $charge_result = json_decode( $charge_response);

        if (!$charge_result || $charge_result->ok === false) {

            return array('success' => false, 'message' => 'Could not establish an order: '.$charge_result->message);
        }

        return  $charge_response;
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
     * @param array $data
     */
    public function createCharge($accessToken, $data)
    {

        $body = $data['body'];
        file_put_contents(__DIR__ . '/log.json', "\n" . 'Body Content From createCharge: '."\n" . $body  . "\n", FILE_APPEND);
        $this->curl->addHeader('authorization', "Bearer  $accessToken");

        $url = $data['endpoint'] . '/hosted-page';

        $this->curl->post($url, $body);

        // output of curl request
        $result = $this->curl->getBody();

        return $result;
    }
}
