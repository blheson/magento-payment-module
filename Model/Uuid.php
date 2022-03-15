<?php

namespace RKFL\Rocketfuel\Model;

use http\Exception;
use Magento\Sales\Api\Data\OrderInterface;
use RKFL\Rocketfuel\Api\UuidInterface;
use Magento\Framework\App\RequestInterface;
use RKFL\Rocketfuel\Model\Rocketfuel;
use RKFL\Rocketfuel\Model\Curl;
use RKFL\Rocketfuel\Model\Order;

/**
 * @api
 */
class Uuid implements UuidInterface
{
    const TESTING = true;

    
    /**
     * @var RequestInterface
     */
    protected $request;

 

    /**
     * @var string
     */
    protected $merchantId;

    protected $rfService;
    protected $modelOrder;

    /**
     * Callback constructor.
     * @param RequestInterface $request
     * @param OrderInterface $order
     * @param Curl $curl
     * @param \RKFL\Rocketfuel\Model\Rocketfuel $rocketfuel
     * @param \RKFL\Rocketfuel\Model\Order $modelOrder
     */
    public function __construct(
        RequestInterface $request,
    
        Curl $curl,
        Rocketfuel $rocketfuel
    
    ) {
        $this->rfService = $rocketfuel;
        $this->request = $request;
 
        $this->curl = $curl;
      
    }


    public function getUUID(){
        // $post = $this->validate($this->request->getPost());
       
        // if (!$this->rfService->getEmail() || !$this->rfService->getPassword()) {
        //     return array('error' => 'true', 'message' => 'Payment gateway not completely configured');
        // }

        // $credentials = array(
        //     'email' => $this->rfService->getEmail(),
        //     'password' => $this->rfService->getPassword()
        // );

        // $data = array(
        //     'cred' => $credentials,
        //     'endpoint' => $this->rfService->getEndpoint(),
        //     'body' => $payload
        // );

        // $response = $this->curl->processPayment($data);


        // $processResult = json_decode($response);

        // if (!$processResult) {

        //     return array('error' => 'true', 'message' => 'There was an error in the process');
        // }

        // $userData = json_encode(array(
            
        // ));

        // $resultData = array('uuid' => $processResult->result->uuid, 'userData' => $userData, 'env' => $this->rfService->getEnvironment());

        // return $resultData;
        return 'here';
    }
 
   
 
}
