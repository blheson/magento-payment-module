<?php

namespace RKFL\Rocketfuel\Model;

use http\Exception;
use Magento\Sales\Api\Data\OrderInterface;
use RKFL\Rocketfuel\Api\BackendInterface;
use Magento\Framework\App\RequestInterface;
use RKFL\Rocketfuel\Model\Rocketfuel;
use RKFL\Rocketfuel\Model\Curl;
use RKFL\Rocketfuel\Model\Order;

/**
 * @api
 */
class Backend extends \Magento\Framework\Model\AbstractModel implements BackendInterface
{
    const TESTING = true;

    protected $request_keys = [
        'type',
        'signature',
        'data'
    ];

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var string
     */
    protected $merchantId;
    /**
     * @var \RKFL\Rocketfuel\Model\Rocketfuel $rfService
     */
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
        OrderInterface $order,
        Curl $curl,
        Rocketfuel $rocketfuel,
        Order $modelOrder
    ) {
        $this->rfService = $rocketfuel;
        $this->request = $request;
        $this->order = $order;
        $this->curl = $curl;
        $this->modelOrder = $modelOrder;
    }

    /**
     * Api callback endpoint
     *
     * @return mixed
     */
    public function postCallback()
    {

        $post = $this->validate($this->request->getPost());

        $validity = $this->validateSignature($post);

        if (!$validity) {
            return array("error" => true, "message" => "Bad request");
        }

        $order = $this->order->load($post->data->offerId);

        if ($order) {

            $order
                ->setState(\Magento\Sales\Model\Order::STATE_COMPLETE)
                ->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);

            $order->save();

            //Todo response
            return json_encode([
                'status' => 'ok'
            ]);
        } else {

            return json_encode([
                'status' => 'error',
                'signature not valid'
            ]);
        }
    }
    public function getUUID()
    {

        return '';
    }
    public function postUUID()
    {

        $post = $this->validate($this->request->getPost());

        file_put_contents(__DIR__ . '/log.json', "\n" . "Thepost : " . json_encode($post), FILE_APPEND);

        if (!$this->rfService->getEmail() || !$this->rfService->getPassword()) {
            return array('error' => true, 'message' => 'Payment gateway not completely configured');
        }
        if (!$post->amount || !$post->currency || !$post->cart) {
            return array('error' => true, 'message' => 'Payment gateway not completely configured');
        }
        $payload = array(
            'cart' => json_decode($post->cart),
            'amount' => $post->amount,
            'currency' => $post->currency,
            'merchant_id' => $this->rfService->getMerchantId(),
            'order' => md5(microtime()) . '-' . bin2hex(random_bytes(5))
        );
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


        $processResult = $response;

        file_put_contents(__DIR__ . '/log.json', "\n" . "The result : " . json_encode($processResult), FILE_APPEND);
        if (!$processResult) {

            return array('error' => 'true', 'message' => 'There was an error in the process');
        }


        $resultData = array('uuid' => $processResult->result->uuid, 'merchantAuth' => $this->rfService->merchantAuth(), 'env' => $this->rfService->getEnvironment(), 'temporaryOrderId' => $payload['order']);

        file_put_contents(__DIR__ . '/log.json', "\n" . "The endpoint spew : " . json_encode($resultData), FILE_APPEND);
        return json_encode($resultData);
    }
    /**
     *  Get callback function
     *  for check exists callback url
     */
    public function getCallback()
    {
        return json_encode([
            'callback_status' => 'ok'
        ]);
    }

    /**
     * get order payload for rocketfuel extension
     *
     * @param $id
     * @return array|false|string
     */
    public function getRocketfuelPayload(int $id)
    {
        return json_encode(
            $this->rfService->getOrderPayload(
                $this->order->load($id)
            )
        );
    }

    /**
     * validate post body
     *
     * @param $request
     * @return object
     */
    protected function validate($request, $type = 'post')
    {
        foreach ($this->request_keys as $key) {
            if ($type === 'post' ? !property_exists($request, $key) : !array_key_exists($key, $request)) {
                //todo throw exception
            };
        }
        return (object)$request;
    }

    /**
     * @param $request
     * @return int
     */
    protected function validateSignature($request)
    {

        $public_key = openssl_pkey_get_public(
            file_get_contents(dirname(__FILE__) . '/../key/.rf_public.key')
        );
        $data = json_decode($request->data);

        $order = $this->order->load($data->offerId);
        //todo for testing
        //return $order;

        if (openssl_verify(
            json_encode($this->rfService->getOrderPayload($order)),
            base64_decode($request->signature),
            $public_key,
            'SHA256'
        )) {
            return $order;
        } else {
            //todo throw exception
            // print_r('error');
            return false;
        }
    }

    /**
     * Update order post body
     *
     * @param $request
     * @return object
     */
    public function updateOrder()
    {

        $post = $this->validate($this->request->getPost());

        $order = $this->order->load($post->orderId);

        switch ($post->status) {
            case '101':
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING; //Fix partial payment
                break;
            case '1':
            case 'completed':
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                break;
            case '-1':
                $status = \Magento\Sales\Model\Order::STATE_CANCELED;
            case '0':
            default:
                $status = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
                break;
        }

        $order
            ->setState($status)
            ->setStatus($status);

        $order->addStatusHistoryComment('Order has been automatically set from Rocketfuel plugin.', false);

        $order->save();
        file_put_contents(__DIR__ . '/log.json', "\n The status of order \n" . json_encode($status) . "\n The status of order  \n", FILE_APPEND);

        return json_encode(array('success' => true, 'message' => 'Order was successfully updated with status: ' . $status));
    }
    /**
     * Validate post body
     *
     * @param int $orderId
     * @return object
     */
    public function getAuth()
    {

        $result = $this->modelOrder->processOrderWithRKFL(1);
    }
    public function swapOrderId()
    {
        $post = $this->validate($this->request->getPost());

        $data = json_encode(array(
            'tempOrderId' => $post->temporaryOrderId,
            'newOrderId' =>  $post->newOrderId
        ));


        $order_payload = $this->rfService->getEncrypted($data, false);


        $merchant_id = base64_encode($this->rfService->getMerchantId());

        $body = json_encode(array('merchantAuth' => $order_payload, 'merchantId' => $merchant_id));

        // $args = array(
        //     'timeout'    => 45,
        //     'headers' => array('Content-Type' => 'application/json'),
        //     'body' => $body
        // );

        $data = array(
            'endpoint' => $this->rfService->getEndpoint(),
            'body' => $body
        );

        $response = $this->curl->swapOrderId($data);


        file_put_contents(__DIR__ . '/log.json', "\n First Swap was loaded \n" . json_encode($response) . "\n Swap was loaded end \n", FILE_APPEND);

        return $response;
    }
}
