<?php

namespace RKFL\Rocketfuel\Model;

use RKFL\Rocketfuel\Model\Rocketfuel;
use RKFL\Rocketfuel\Model\Curl;
use RKFL\Rocketfuel\Api\OrderInterface;
use Magento\Store\Model\Store as Store;

class Order extends \Magento\Sales\Block\Order\Totals implements OrderInterface
{
    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;

    /**
     * Order constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Rocketfuel $rocketfuel
     * @param Curl $curl
     * @param array $data
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        Rocketfuel $rocketfuel,
        Curl $curl,
        Store $store,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->rfService = $rocketfuel;
        $this->curl = $curl;
        $this->store = $store;
    }

    /**
     * get order entity
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->_order = $this->_orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId()
        );
    }

    public function getPaymentProcessDetails(){

    }

    /**
     * get active logged in user customer id
     *
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }

    /**
     *  get iframe url from settings
     *
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->rfService->getIframeUrl();
    }
    /**
     *  Get Environment from settings
     *
     * @return string
     */
    public function getEnvironment() {
        
        return $this->rfService->getEnvironment();

    }
    /**
     * Return payment method title for specific order Id
     */
    public function getPaymentCode()
    {
        return 'RocketFuel';

    }

    /**
     * Check is order payment is rocketfuel and status processing
     *
     * @return bool
     */
    public function isNotPayed()
    {
        return (($this->getPaymentCode() === 'rocketfuel') & ($this->getOrder()->getStatus() === 'processing'));
    }


    public function getRocketfuelPayload($order){

        return json_encode(
            $this->rfService->getOrderPayload(
                $order
            )
        );

    }
    public function processOrderWithRKFL( $orderId = 1 ){
    

        $order =   $this->_orderFactory->create()->loadByIncrementId($orderId);

        $payload  = $this->getRocketfuelPayload($order);

        $credentials = array(
            'email' => $this->rfService->getEmail(),
            'password' => $this->rfService->getPassword()
        );
       
        $data = array(
            'cred' => $credentials,
            'endpoint' => $this->rfService->getEndpoint(),
            'body' => $payload
        );

        $response = $this->curl->processPayment($data);


       $processResult = json_decode( $response );

       if(  !$processResult ){

        return json_encode(array('success' => 'false','message'=>'There was an error in the process '  ));

       }


        $userData = json_encode(array(
            'first_name' => $order->getBillingAddress()->getFirstName(),
            'last_name' => $order->getBillingAddress()->getLastName(),
            'email' => $order->getBillingAddress()->getEmail(),
            'merchant_auth' => $this->rfService->merchantAuth()
        ));
        

        
        
        $resultData = array('uuid'=>$processResult->result->uuid, 'userData'=>$userData,'env'=> $this->rfService->getEnvironment() );



        return $resultData;
    }
      /**
     * Validate post body
     *
     * @param int $orderId
     * @return object
     */
    public function getAuth()
    {
  

        $result = $this->processOrderWithRKFL(1);

    }
    /**
     * Get store url
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->store->getBaseUrl() 
    }
}
